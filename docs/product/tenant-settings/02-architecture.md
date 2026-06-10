# Tenant Settings — Architecture

**Status:** Draft  
**Level:** Developer reading  
**Purpose:** Understand class design, layer structure, and data flow

---

## 🏗️ Layer Structure

```
┌──────────────────────────────────────────┐
│ HTTP Layer (Presentation)                │
│ SettingController / SettingRequest        │
└────────────┬─────────────────────────────┘
             ↓ depends on
┌──────────────────────────────────────────┐
│ Application Layer (Orchestration)        │
│ UpdateTenantSettingUseCase                │
│ GetTenantSettingsUseCase                  │
│ TenantSettingDTO                          │
└────────────┬─────────────────────────────┘
             ↓ depends on
┌──────────────────────────────────────────┐
│ Domain Layer (Business Logic)            │
│ TenantSettingEntity                       │
│ TenantSettingRepositoryInterface          │
│ TenantSettingDefaults (constant)          │
└────────────┬─────────────────────────────┘
             ↓ depends on
┌──────────────────────────────────────────┐
│ Infrastructure Layer (Implementation)    │
│ EloquentTenantSettingRepository           │
│ TenantSettingService                      │
└──────────────────────────────────────────┘
```

---

## 📦 Domain Layer

### TenantSettingEntity
```php
namespace App\Domain\TenantSetting\Entities;

class TenantSettingEntity {
    public function __construct(
        public readonly ?int   $id,
        public readonly int    $tenantId,
        public readonly string $key,      // e.g., 'email.task_assigned'
        public readonly mixed  $value,    // boolean, integer, string, array
    ) {}
}
```

### TenantSettingRepositoryInterface
```php
namespace App\Domain\TenantSetting\Repositories;

interface TenantSettingRepositoryInterface {
    
    /**
     * Return all stored settings for a tenant as a flat [dot.key => value] map.
     * Merged with defaults (stored values override defaults).
     */
    public function getAllForTenant(int $tenantId): array;
    
    /**
     * Upsert multiple [dot.key => value] pairs for a tenant in one atomic operation.
     * Cache is flushed after write.
     */
    public function upsertMany(int $tenantId, array $pairs): void;
}
```

### TenantSettingDefaults
```php
namespace App\Domain\Tenant;

class TenantSettingDefaults {
    public const DEFAULTS = [
        'email' => [
            'task_assigned' => true,
            'task_status_changed' => true,
            'tenant_member_added' => true,
            'tenant_member_removed' => true,
            'tenant_role_changed' => true,
        ],
        'notifications' => [
            'retention_days' => 30,
        ],
        'localization' => [
            'timezone' => 'UTC',
            'locale' => 'en',
            'date_format' => 'd/m/Y',
        ],
        'members' => [
            'default_role' => 'member',
        ],
    ];
}
```

---

## 📱 Application Layer

### UpdateTenantSettingDTO
```php
namespace App\Application\Tenant\DTOs;

class UpdateTenantSettingDTO {
    public function __construct(
        public readonly string $section,  // e.g., 'email', 'notifications'
        public readonly array  $values,   // e.g., ['task_assigned' => false, ...]
    ) {}

    public static function fromArray(string $section, array $data): self {
        return new self(
            section: $section,
            values: $data[$section] ?? [],
        );
    }
}
```

### UpdateTenantSettingUseCase
```php
namespace App\Application\Tenant\UseCases;

class UpdateTenantSettingUseCase {
    public function __construct(
        private readonly TenantSettingRepositoryInterface $repository,
    ) {}
    
    public function execute(
        int $tenantId,
        UpdateTenantSettingDTO $dto,
    ): void {
        // 1. Validate section exists in DEFAULTS
        // 2. Build dot-notation keys: "email.task_assigned", etc.
        // 3. Upsert all pairs for this section via repository
    }
}
```

### GetTenantSettingsUseCase
```php
namespace App\Application\Tenant\UseCases;

class GetTenantSettingsUseCase {
    public function execute(int $tenantId): array {
        // Returns merged: stored settings + defaults
        // E.g., { email: {...}, notifications: {...} }
    }
}
```

---

## 🔧 Infrastructure Layer

### EloquentTenantSettingRepository
```php
namespace App\Infrastructure\Persistence\Repositories;

class EloquentTenantSettingRepository implements TenantSettingRepositoryInterface {
    
    public function getAllForTenant(int $tenantId): array {
        // 1. Fetch all rows for this tenant from tenant_settings
        // 2. Convert [(tenant_id, key, value), ...] → [key => value]
        // 3. Merge with defaults (stored values override defaults)
        // 4. Cache with tag for this tenant, TTL 600s
        return Cache::tags(["tenant:{$tenantId}:settings"])
            ->remember("tenant_settings:{$tenantId}", 600, function () use ($tenantId) {
                $stored = TenantSetting::where('tenant_id', $tenantId)
                    ->pluck('value', 'key')
                    ->toArray();
                return array_merge_recursive(TenantSettingDefaults::DEFAULTS, $stored);
            });
    }
    
    public function upsertMany(int $tenantId, array $pairs): void {
        // For each [key => value] pair:
        //   updateOrCreate(['tenant_id' => $tenantId, 'key' => $key], ['value' => $value])
        foreach ($pairs as $key => $value) {
            TenantSetting::updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => $key],
                ['value' => $value],
            );
        }
        
        // Flush cache for this tenant
        Cache::tags(["tenant:{$tenantId}:settings"])->flush();
    }
}
```

### TenantSetting Eloquent Model
```php
namespace App\Models;

class TenantSetting extends Model {
    protected $fillable = ['tenant_id', 'key', 'value'];
    protected $casts = ['value' => 'json'];
    // No TenantScope — settings queried explicitly per admin view
}
```

### Global Helper
```php
// app/Shared/helpers.php

function tenantSetting(
    string $key,
    mixed $default = null,
    ?int $tenantId = null
): mixed {
    $tenantId = $tenantId ?? tenantContext()->getId();
    $stored = app(TenantSettingRepositoryInterface::class)->getAllForTenant($tenantId);
    
    return $stored[$key] ?? data_get(TenantSettingDefaults::DEFAULTS, $key, $default);
}
```

---

## 🎨 HTTP Layer

### TenantSettingController

The Settings page is split into a **submenu of sections** (Email,
Notifications, Localization, Members), each with its own URL segment
`{section}`. One controller serves all sections — it just resolves the view
by section name and delegates to UseCases.

```php
namespace App\Http\Controllers\Admin;

class TenantSettingController extends Controller {

    private const SECTIONS = ['email', 'notifications', 'localization', 'members'];

    public function __construct(
        private readonly GetTenantSettingsUseCase $getUseCase,
        private readonly UpdateTenantSettingUseCase $updateUseCase,
    ) {}

    public function index(int $tenantId, string $section = 'email') {
        if (!in_array($section, self::SECTIONS, true)) {
            abort(404);
        }

        $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
        $this->authorize('edit', $tenant); // Uses TenantPolicy::edit (tenant:edit permission)

        $settings = $this->getUseCase->execute($tenantId);

        return view("admin.pages.tenant.settings.{$section}", [
            'tenantId' => $tenantId,
            'tenant'   => $tenant,
            'section'  => $section,
            'settings' => $settings,
        ]);
    }

    public function update(int $tenantId, string $section, UpdateTenantSettingRequest $request) {
        if (!in_array($section, self::SECTIONS, true)) {
            abort(404);
        }

        try {
            $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
            $this->authorize('edit', $tenant);

            $dto = UpdateTenantSettingDTO::fromArray($section, $request->validated());
            $this->updateUseCase->execute($tenantId, $dto);

            return redirect()
                ->route('tenant.settings.index', [$tenantId, $section])
                ->with('success', 'Settings updated successfully.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
```

### Routes

```php
// routes/web.php
Route::middleware(['auth', 'chooseTenant'])
    ->prefix('admin/tenant/{tenantId}/settings')
    ->name('tenant.settings.')
    ->group(function () {
        Route::get('/{section?}', [SettingController::class, 'index'])->name('index');
        Route::post('/{section}', [SettingController::class, 'update'])->name('update');
    });
```

- `GET /admin/tenant/5/settings` → defaults to `section = 'email'`
- `GET /admin/tenant/5/settings/notifications` → renders the Notifications section
- Sidebar/submenu links point to each section URL; active link highlighted via `request()->routeIs(...)` or comparing `$section`

---

## 📊 Data Flow

### Flow: Admin Changes Email Settings

```
1. User toggles checkboxes in Email Settings form
2. Form POST to /admin/tenant/5/settings/email
3. TenantSettingController::update() receives request
4. UpdateTenantSettingRequest validates:
   - section is 'email'
   - each boolean field required
5. UpdateTenantSettingUseCase::execute($tenantId, $dto)
   - Validates section exists in DEFAULTS
   - Builds dot-notation keys: "email.task_assigned", "email.task_status_changed", ...
   - Calls repository->upsertMany($tenantId, $pairs)
6. EloquentTenantSettingRepository::upsertMany()
   - For each key/value pair:
     - updateOrCreate(['tenant_id' => 5, 'key' => 'email.task_assigned'], ['value' => false])
   - Flushes cache tag: "tenant:5:settings"
7. Redirects with success toast
8. Next time NotificationService runs:
   - Calls tenantSetting('email.task_assigned', true, 5)
   - Fetches fresh (cache miss) or cached list from tenant_settings table
   - Returns: false (from DB)
   - Skips email, sends in-app only
```

### Flow: Code Reads Setting

```
NotificationService::notifyOne()
  ↓
$emailEnabled = tenantSetting('email.task_assigned', true, $tenantId);
  ↓
Calls repository->getAllForTenant($tenantId)
  ↓
Queries tenant_settings table WHERE tenant_id = $tenantId
  ↓
Converts rows to [key => value] and merges with DEFAULTS
  ↓
Caches result with tag "tenant:{id}:settings", TTL 600s
  ↓
Helper uses data_get() to return stored['email.task_assigned'] or default
  ↓
Returns: false (from DB)
  ↓
If email not enabled, skip email
```

---

## 🔐 Security Model

### Access Control
```
Controller: $this->authorize('edit', $tenant)
└─ Uses TenantPolicy::edit($user, $tenant)
└─ Checks: $user->hasPermissionInTenant('tenant:edit', $tenant->id)
└─ Only Admin/Owner allowed; others: 403 Forbidden

Validation:
├─ Section must exist in DEFAULTS
├─ Each boolean/integer/string field type-validated
└─ No SQL injection (Eloquent parameterized + unique constraint prevents key injection)
```

---

## 🗂️ File Structure

```
app/
├── Domain/Tenant/
│   └── TenantSettingDefaults.php (shared, already exists)
├── Domain/TenantSetting/
│   ├── Entities/TenantSettingEntity.php
│   └── Repositories/TenantSettingRepositoryInterface.php
├── Application/Tenant/
│   ├── DTOs/UpdateTenantSettingDTO.php (already exists)
│   └── UseCases/
│       ├── UpdateTenantSettingUseCase.php
│       └── GetTenantSettingsUseCase.php
├── Infrastructure/Persistence/Repositories/
│   └── EloquentTenantSettingRepository.php
├── Models/
│   └── TenantSetting.php
├── Http/Controllers/Admin/
│   └── TenantSettingController.php
├── Http/Requests/Tenant/
│   └── UpdateTenantSettingRequest.php
└── Shared/helpers.php

database/
└── migrations/
    └── 2026_06_10_xxxxxx_create_tenant_settings_table.php

resources/views/admin/pages/tenant/settings/
├── email.blade.php           (extends layout, section = email)
├── notifications.blade.php   (extends layout, section = notifications)
├── localization.blade.php    (extends layout, section = localization)
└── members.blade.php         (extends layout, section = members)

resources/views/admin/layouts/
└── tenant-settings.blade.php (shared layout: renders submenu sidebar + @yield)

resources/views/admin/components/
├── settings-sidebar.blade.php  (submenu: Email/Notifications/Localization/Members)
└── setting-toggle.blade.php

tests/
└── Feature/Tenant/
    └── TenantSettingTest.php
```

---

## 🔄 Dependencies

**Incoming:** (who uses TenantSetting?)
- NotificationService (checks email settings)
- CleanupCommand (reads retention_days)
- Views (apply timezone)
- AttachUserUseCase (applies default role)

**Outgoing:** (what does TenantSetting depend on?)
- TenantRepository (read/write tenants table)
- TenantContext (get current tenant)

---

## Next: Implementation

Read [03-implementation-plan.md](./03-implementation-plan.md) for step-by-step code.
