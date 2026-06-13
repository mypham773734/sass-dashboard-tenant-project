# Implementation Steps: Tenant Role → Spatie RBAC Consolidation

Checklist chi tiết theo từng file, đi kèm pseudocode/snippet để implement. Đi theo đúng thứ tự
A → E như trong [tenant-role-rbac-consolidation.md](./tenant-role-rbac-consolidation.md). Mỗi
phase nên kết thúc bằng `php artisan test` xanh trước khi sang phase tiếp theo.

---

## Phase A — Nền tảng Domain & Infrastructure

### A1. `app/Domain/Role/Entities/RoleEntity.php`

Sửa constructor đang rỗng/lỗi:

```php
<?php

namespace App\Domain\Role\Entities;

class RoleEntity
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $tenantId,
        public readonly string $name,
        public readonly string $guardName,
    ) {}
}
```

### A2. `app/Domain/Role/Repositories/RoleRepositoryInterface.php`

Thay nội dung hiện tại (chỉ có `create()` chưa được dùng/implement ở đâu — bỏ luôn) bằng:

```php
<?php

namespace App\Domain\Role\Repositories;

use App\Domain\Role\Entities\RoleEntity;

interface RoleRepositoryInterface
{
    public function findByNameAndTenant(string $name, int $tenantId): ?RoleEntity;

    /** @return RoleEntity[] */
    public function getRolesByTenant(int $tenantId): array;

    public function getUserRoleForTenant(int $userId, int $tenantId): ?RoleEntity;

    /** Gỡ mọi role scope-theo-tenant hiện có của user, rồi gán $roleName. */
    public function assignUserRole(int $userId, int $tenantId, string $roleName): void;

    /** @return int[] user id */
    public function findUserIdsByTenantAndRoles(int $tenantId, array $roleNames): array;
}
```

### A3. `app/Infrastructure/Persistence/Repositories/EloquentRoleRepository.php`

File hiện đang rỗng (1 dòng). Tạo nội dung đầy đủ:

```php
<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Role\Entities\RoleEntity;
use App\Domain\Role\Repositories\RoleRepositoryInterface;
use App\Models\Role;
use App\Models\User;

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findByNameAndTenant(string $name, int $tenantId): ?RoleEntity
    {
        $role = Role::where('name', $name)
            ->where('tenant_id', $tenantId)
            ->where('guard_name', 'web')
            ->first();

        return $role ? $this->toEntity($role) : null;
    }

    public function getRolesByTenant(int $tenantId): array
    {
        return Role::where('tenant_id', $tenantId)
            ->where('guard_name', 'web')
            ->get()
            ->map(fn (Role $role) => $this->toEntity($role))
            ->all();
    }

    public function getUserRoleForTenant(int $userId, int $tenantId): ?RoleEntity
    {
        $role = User::findOrFail($userId)
            ->rolesForTenant($tenantId)
            ->first();

        return $role ? $this->toEntity($role) : null;
    }

    public function assignUserRole(int $userId, int $tenantId, string $roleName): void
    {
        $user = User::findOrFail($userId);

        $newRole = Role::where('name', $roleName)
            ->where('tenant_id', $tenantId)
            ->where('guard_name', 'web')
            ->firstOrFail();

        // Giữ invariant "1 role / tenant": gỡ role(s) cũ của tenant này trước khi gán role mới
        $user->rolesForTenant($tenantId)->each(
            fn (Role $oldRole) => $user->removeRole($oldRole)
        );

        $user->assignRole($newRole);
    }

    public function findUserIdsByTenantAndRoles(int $tenantId, array $roleNames): array
    {
        return User::whereHas('roles', function ($q) use ($tenantId, $roleNames) {
            $q->where('roles.tenant_id', $tenantId)->whereIn('roles.name', $roleNames);
        })->pluck('id')->all();
    }

    private function toEntity(Role $model): RoleEntity
    {
        return new RoleEntity(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            guardName: $model->guard_name,
        );
    }
}
```

> ⚠️ `rolesForTenant()` trả về `Collection` (đã `->get()` sẵn trong `app/Models/User.php`), nên
> `->each()`/`->first()` dùng trực tiếp được, không cần gọi `->get()` lại.

### A4. `app/Providers/AppServiceProvider.php`

Thêm 2 import (cùng nhóm với các import Clean Architecture khác, sau dòng `use
App\Infrastructure\Persistence\Repositories\EloquentTaskRepository;`):

```php
use App\Domain\Role\Repositories\RoleRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentRoleRepository;
```

Thêm binding trong `boot()`, cùng khối "Clean Architecture bindings", ngay sau binding
`TaskRepositoryInterface`:

```php
$this->app->bind(
    RoleRepositoryInterface::class,
    EloquentRoleRepository::class,
);
```

### A5. Verify Phase A

- `php artisan test` — phải xanh (chưa có gì dùng class mới nên không thể fail).
- `php artisan tinker`:
  ```php
  app(\App\Domain\Role\Repositories\RoleRepositoryInterface::class)
      ->findByNameAndTenant('owner', 1);
  // → RoleEntity { id: ..., tenantId: 1, name: 'owner', guardName: 'web' }

  app(\App\Domain\Role\Repositories\RoleRepositoryInterface::class)
      ->getUserRoleForTenant(1, 1);
  ```

---

## Phase B — Fix bug đồng bộ (dual-write)

### B1. `app/Application/Tenant/UseCases/AttachUserToTenantUseCase.php`

Thêm constructor param + 1 dòng gọi sau `attachUser()`:

```php
use App\Domain\Role\Repositories\RoleRepositoryInterface;

class AttachUserToTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,       // ← mới
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    public function execute(int $tenantId, int $userId, string $role = 'member'): void
    {
        $this->tenantRepository->attachUser($tenantId, $userId, $role);
        $this->roleRepository->assignUserRole($userId, $tenantId, $role);  // ← mới

        // Notify admins that a new member joined
        $this->notifySystem($tenantId, $userId);
    }
    // ... notifySystem() giữ nguyên
}
```

### B2. `app/Application/Tenant/UseCases/ChangeUserRoleUseCase.php`

> ⚠️ **Bug có sẵn cần lưu ý khi sửa**: code hiện tại gọi `updateUserRole()` (ghi `tenant_user.role`
> = role MỚI) **trước khi** `notifySystem()` đọc `getUserRole()` để lấy `old_role` — tức
> `old_role` đang luôn **bằng `new_role`** (đọc sau khi đã ghi). Khi thêm `assignUserRole()`, tận
> dụng luôn cơ hội đọc role-cũ-qua-Spatie **trước** khi ghi gì cả, để không lặp lại lỗi này cho
> nguồn `old_role` mới.

```php
use App\Domain\Role\Repositories\RoleRepositoryInterface;

class ChangeUserRoleUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,       // ← mới
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    public function execute(int $tenantId, int $userId, string $newRole): void
    {
        // Đọc role cũ qua Spatie TRƯỚC khi ghi gì — tránh lặp lại bug "old == new"
        $oldRoleViaSpatie = $this->roleRepository->getUserRoleForTenant($tenantId, $userId)?->name;

        $this->tenantRepository->updateUserRole($tenantId, $userId, $newRole); // giữ tạm (Phase D mới bỏ)
        $this->roleRepository->assignUserRole($userId, $tenantId, $newRole);   // ← mới

        $this->notifySystem($tenantId, $userId, $newRole, $oldRoleViaSpatie);
    }

    private function notifySystem(int $tenantId, int $userId, string $newRole, ?string $oldRole)
    {
        $user = $this->userRepository->findById($userId);
        $authorName = authContext()->getUser()->name;
        $this->notificationService->notifyOne(
            event:    'tenant.role_changed',
            tenantId: $tenantId,
            userId:   $userId,
            context:  [
                'target_user_id'   => $userId,
                'target_user_name' => $user->name,
                'old_role'         => $oldRole ?? $newRole, // fallback nếu user chưa có role nào trước đó
                'new_role'         => $newRole,
                'actor_name'       => $authorName,
            ]
        );
    }
}
```

> Lưu ý ký tự: method `getUserRoleForTenant(int $userId, int $tenantId)` — chú ý **thứ tự tham
> số** khớp với interface định nghĩa ở A2 (`userId` trước, `tenantId` sau), khác thứ tự
> `tenantRepository->updateUserRole($tenantId, $userId, ...)`. Gọi nhầm thứ tự sẽ không lỗi cú
> pháp (cả 2 đều `int`) nhưng query sai bảng/sai điều kiện — double-check khi viết.

### B3. `app/Infrastructure/Persistence/Repositories/EloquentUserRepository.php`

Sửa `findAdminsByTenant()` (hiện đang query `tenant_user.role`):

```php
public function findAdminsByTenant(int $tenantId): array
{
    return User::whereHas('roles', function ($q) use ($tenantId) {
        $q->where('roles.tenant_id', $tenantId)->whereIn('roles.name', ['owner', 'admin']);
    })->pluck('id')->toArray();
}
```

(Bản cũ dùng `whereHas('tenants', ...)` + `pluck('users.id')`; bản mới query thẳng trên
`User::whereHas('roles', ...)` nên `pluck('id')` — không cần prefix `users.` vì không join.)

### B4. `app/Models/User.php`

Sửa `isSystemAdmin()` + xoá const lệch tên:

```php
use App\Domain\User\Enums\RoleEnum;

// Xoá: private const string RoleSystemAdmin = 'systemAdmin';

public function isSystemAdmin(){
    return $this->roles()->where('name', RoleEnum::SYSTEM_ADMIN->value)->first();
}
```

### B5. Xoá file `app/Domain/User/Enums/RoleDefault.php`

Trùng y nguyên `RoleEnum.php`, không có reference nào → xoá thẳng.

### B6. Regression test (mới)

Tạo `tests/Feature/Tenant/TenantRoleAssignmentTest.php` (hoặc thêm vào suite hiện có nếu đã có
file tương tự) — 2 case:

1. **Attach member mới** → assert `model_has_roles` có dòng với `role_id` = id của role
   `'member'` của tenant đó, VÀ `$user->hasPermissionInTenant('task:create', $tenantId) === true`
   (permission mà role `member` có theo `config/rolePermissionDefault.php`).
2. **Đổi role** member → admin → assert `$user->hasPermissionInTenant('project:create',
   $tenantId) === true` ngay sau khi `ChangeUserRoleUseCase::execute()` chạy (permission chỉ
   `admin`/`owner` mới có).

### B7. Manual verify

- Vào UI team management, invite 1 user mới → kiểm tra user đó thấy đúng menu/action theo role
  `member`.
- Đổi role user đó → `admin` → reload, kiểm tra menu/action admin xuất hiện ngay (không cần
  logout/login lại — nếu Spatie cache permission gây trễ, gọi
  `app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions()` trong
  `assignUserRole()` sau `assignRole()`/`removeRole()`).

---

## Phase C — Migration backfill

### C1–C2. `database/migrations/2026_06_13_000000_backfill_model_has_roles_from_tenant_user.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('tenant_user')->select('tenant_id', 'user_id', 'role')->get();

        foreach ($rows as $row) {
            if (empty($row->role)) {
                continue;
            }

            $role = Role::firstOrCreate(
                ['name' => $row->role, 'tenant_id' => $row->tenant_id, 'guard_name' => 'web'],
            );

            $exists = DB::table('model_has_roles')
                ->where('role_id', $role->id)
                ->where('model_id', $row->user_id)
                ->where('model_type', \App\Models\User::class)
                ->where('tenant_id', $row->tenant_id)
                ->exists();

            if (! $exists) {
                DB::table('model_has_roles')->insert([
                    'role_id'    => $role->id,
                    'model_type' => \App\Models\User::class,
                    'model_id'   => $row->user_id,
                    'tenant_id'  => $row->tenant_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Backfill dữ liệu — không reverse (giữ nguyên model_has_roles đã tạo).
    }
};
```

> Dùng `Role::firstOrCreate(...)` ở đây an toàn vì `app/Models/Role.php::create()` đã override để
> check trùng theo `tenant_id` (xem phần "Vai trò Role model" đã research trước đó). Nếu tenant đó
> chưa có role tên này (trường hợp hiếm — data cũ lệch convention), `firstOrCreate` sẽ tạo role
> **không có permission nào** — chấp nhận được vì là edge-case dữ liệu cũ, nhưng nên log ra để
> review thủ công sau (`Log::warning("Backfill created empty role '{$row->role}' for tenant
> {$row->tenant_id}")`).

### C3. Chạy migration

```bash
php artisan migrate
```

### C4. Verify

```sql
SELECT COUNT(*) FROM tenant_user tu
LEFT JOIN model_has_roles mhr
  ON mhr.model_id = tu.user_id
  AND mhr.model_type = 'App\\Models\\User'
  AND mhr.tenant_id = tu.tenant_id
LEFT JOIN roles r ON r.id = mhr.role_id AND r.name = tu.role
WHERE tu.role IS NOT NULL AND r.id IS NULL;
-- kỳ vọng: 0
```

---

## Phase D — Cutover & drop column

> ⚠️ Chỉ chạy phase này sau khi Phase C verify = 0 trên **production data** (không chỉ local).

### D1. `app/Application/Tenant/UseCases/ChangeUserRoleUseCase.php`

Bỏ dòng `$this->tenantRepository->updateUserRole(...)` và `$oldRoleViaSpatie` giờ là nguồn duy
nhất:

```php
public function execute(int $tenantId, int $userId, string $newRole): void
{
    $oldRole = $this->roleRepository->getUserRoleForTenant($tenantId, $userId)?->name;

    $this->roleRepository->assignUserRole($userId, $tenantId, $newRole);

    $this->notifySystem($tenantId, $userId, $newRole, $oldRole);
}
```

(`notifySystem()` không đổi so với Phase B.)

### D2. `app/Domain/Tenant/Repositories/TenantRepositoryInterface.php`

Xoá 2 method:

```php
public function getUserRole(int $tenantId, int $userId): ?string;
public function updateUserRole(int $tenantId, int $userId, string $newRole): void;
```

Sửa signature `attachUser`:

```php
public function attachUser(int $tenantId, int $userId): void;  // bỏ $role
```

### D3. `app/Infrastructure/Persistence/Repositories/EloquentTenantRepository.php`

- Xoá implementation `getUserRole()` (dòng 163-172) và `updateUserRole()` (dòng 174-183).
- Sửa `attachUser()`:

```php
public function attachUser(int $tenantId, int $userId): void
{
    $cacheTag = "user:{$userId}:tenants";
    Tenant::withoutGlobalScopes()
        ->findOrFail($tenantId)
        ->users()
        ->attach($userId);   // không còn ['role' => $role]

    Cache::tags([$cacheTag])->flush();
}
```

### D4. Sửa các caller của `attachUser()`

- `AttachUserToTenantUseCase::execute()`:
  ```php
  $this->tenantRepository->attachUser($tenantId, $userId);             // bỏ $role
  $this->roleRepository->assignUserRole($userId, $tenantId, $role);
  ```
- `CreateTenantUseCase::execute()` (`app/Application/Tenant/UseCases/CreateTenantUseCase.php:32`):
  ```php
  $this->tenantRepository->attachUser($created->id, $creatorUserId);             // bỏ $roleAttach
  $this->roleRepository->assignUserRole($creatorUserId, $created->id, $roleAttach); // inject RoleRepositoryInterface
  ```
  → cần thêm `RoleRepositoryInterface` vào constructor của `CreateTenantUseCase`.
- Grep lại `attachUser(` toàn repo để chắc không bỏ sót caller nào khác (lúc viết plan này chỉ
  thấy 2 chỗ trên + `SetupAppUseCase` qua `attchUserTenantWithRole`, file đó dùng
  `attachUser($tenant->id, $user->id, ...)` — xử lý ở Phase E vì liên quan `system_admin`).

### D5. Migration drop column

`database/migrations/2026_06_13_000001_drop_role_from_tenant_user_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_user', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_user', function (Blueprint $table) {
            $table->string('role')->nullable()->after('user_id');
        });
        // Dữ liệu role không khôi phục được — Phase C đã backfill sang model_has_roles là nguồn chuẩn.
    }
};
```

### D6. Chạy & verify

```bash
php artisan migrate
php artisan test
```

---

## Phase E — `system_admin` role (global)

### E1–E2. Seed role + permission global cho `system_admin`

`config/rolePermissionDefault.php` hiện được loop **per-tenant** trong
`SetupDefaultTenantRolesAndPermissionsUseCase` (luôn truyền `tenant_id: $tenant->id`). Role
`system_admin` cần `tenant_id = null` → **không** thêm vào file config này (sẽ bị tạo lại mỗi
tenant, sai với "global").

Thêm 1 config riêng `config/systemAdminPermissions.php`:

```php
<?php

return [
    // Danh sách permission cấp platform — điều chỉnh theo nhu cầu thực tế
    'platform:manage_tenants',
    'platform:manage_users',
    'platform:view_audit_logs',
];
```

Thêm method mới `EloquentRoleRepository::assignGlobalRole()` (và signature trong
`RoleRepositoryInterface`):

```php
// RoleRepositoryInterface
public function assignGlobalRole(int $userId, string $roleName): void;

// EloquentRoleRepository
public function assignGlobalRole(int $userId, string $roleName): void
{
    $permissionNames = config('systemAdminPermissions');

    $role = Role::firstOrCreate(
        ['name' => $roleName, 'tenant_id' => null, 'guard_name' => 'web'],
    );

    $permissions = collect($permissionNames)->map(
        fn (string $name) => Permission::firstOrCreate(
            ['name' => $name, 'tenant_id' => null, 'guard_name' => 'web'],
        )
    );
    $role->syncPermissions($permissions);

    User::findOrFail($userId)->assignRole($role);
}
```

(`use App\Models\Permission;` cần thêm vào import của `EloquentRoleRepository`.)

### E3. `app/Application/Setup/UseCases/SetupAppUseCase.php`

Sửa `attchUserTenantWithRole()` — gọi thêm `assignGlobalRole`, và inject
`RoleRepositoryInterface`:

```php
use App\Domain\Role\Repositories\RoleRepositoryInterface;

class SetupAppUseCase {
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly RoleRepositoryInterface $roleRepository,        // ← mới
        private SetupDefaultTenantRolesAndPermissionsUseCase $setupRolePermissionUseCase
    ){}

    // ...

    private function attchUserTenantWithRole(UserEntity $user, TenantEntity $tenant): void
    {
        if ($tenant->id === null || $user->id === null) {
            throw new \DomainException('Tenant or user id is missing.');
        }

        // Membership + role "owner" cho tenant mặc định (Alpha Tech Solutions)
        $this->tenantRepository->attachUser($tenant->id, $user->id);
        $this->roleRepository->assignUserRole($user->id, $tenant->id, RoleEnum::OWNER->value);

        // Quyền platform-level, không gắn tenant cụ thể
        $this->roleRepository->assignGlobalRole($user->id, RoleEnum::SYSTEM_ADMIN->value);
    }
}
```

> Thay đổi so với bản hiện tại: trước đây `attchUserTenantWithRole` gán `tenant_user.role =
> 'system_admin'` (giá trị không nằm trong bộ owner/admin/member, không có permission set nào ở
> tenant-level). Với thiết kế mới, user bootstrap có **2 vai trò tách biệt**: `owner` của tenant
> "Alpha Tech Solutions" (để dùng app như user thường) + `system_admin` global (để pass
> `CheckSystemAdminNotExists`/`getSystemAdmin()`). Cần xác nhận với business đây có đúng ý định
> không trước khi code Phase E.

### E4. Verify

```bash
php artisan migrate:fresh
php artisan tinker
```
```php
app(\App\Application\Setup\UseCases\SetupAppUseCase::class)->execute();

$user = \App\Models\User::where('email', 'systemadmin@gmail.com')->first();
$user->isSystemAdmin();        // → Role model 'system_admin', không null
$user->hasRole('system_admin'); // → true
app(\App\Domain\User\Repositories\UserRepositoryInterface::class)->getSystemAdmin(); // → User
```

---

## Tổng kết thứ tự file thay đổi theo phase

| Phase | Tạo mới | Sửa | Xoá |
|---|---|---|---|
| A | `EloquentRoleRepository.php` | `RoleEntity.php`, `RoleRepositoryInterface.php`, `AppServiceProvider.php` | — |
| B | test mới (B6) | `AttachUserToTenantUseCase.php`, `ChangeUserRoleUseCase.php`, `EloquentUserRepository.php`, `User.php` | `RoleDefault.php` |
| C | migration backfill | — | — |
| D | migration drop column | `ChangeUserRoleUseCase.php`, `TenantRepositoryInterface.php`, `EloquentTenantRepository.php`, `AttachUserToTenantUseCase.php`, `CreateTenantUseCase.php` | — |
| E | `config/systemAdminPermissions.php` | `RoleRepositoryInterface.php`/`EloquentRoleRepository.php` (+`assignGlobalRole`), `SetupAppUseCase.php` | — |
