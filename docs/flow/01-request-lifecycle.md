# Flow 1: Request Lifecycle — Auth, Tenant Context, Middleware

**Phạm vi:** Từ lúc HTTP request vào tới khi đến được Controller, tập trung vào: session-based auth, multi-tenant context, và global scope.

---

## 1. Routing Setup — `bootstrap/app.php`

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',   // notification broadcasting auth
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn ($request) => route('login'));

        $middleware->alias([
            'chooseTenant' => ChooseCurrentTenant::class,
        ]);

        $middleware->appendToGroup('web', SetDefaultTenant::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SendScheduledEmailsCommand::class)->everyMinute();
    })
```

Hai điểm quan trọng:
- `SetDefaultTenant` được append vào **group `web`** → chạy cho **mọi** request web (kể cả guest, nhưng nó tự check `authContext()->checkLogin()`).
- `chooseTenant` chỉ là alias — phải khai báo ở route nào thì middleware đó mới chạy.

---

## 2. Route Groups — `routes/web.php`

```php
Route::middleware('guest')->group(function () {
    // /login (GET, POST)
});

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('/tenant', TenantController::class);                 // KHÔNG có chooseTenant
        Route::resource('/project', ProjectController::class)->middleware('chooseTenant');
        Route::resource('/task', TaskController::class)->middleware('chooseTenant');
        Route::resource('/user', UserController::class)->middleware('chooseTenant');
        Route::get('/audit', [AuditController::class, 'index'])->middleware('chooseTenant');
        // profile, tenant switch, tenant settings...
    });

    Route::post('/logout', ...);

    // Notification routes — cũng cần chooseTenant
    Route::get('/notifications', ...)->middleware('chooseTenant');
});
```

**Lý do `/admin/tenant` KHÔNG cần `chooseTenant`:** Trang quản lý danh sách tenant của user phải truy cập được **trước khi** chọn tenant (ví dụ user mới chưa có tenant nào, hoặc đang tạo tenant đầu tiên).

---

## 3. Middleware Pipeline (theo thứ tự thực thi)

```
Request
  │
  ▼
[auth]  ── chưa login? → redirect → route('login')  (redirectGuestsTo)
  │
  ▼
[SetDefaultTenant]  (chạy cho mọi route trong group 'web')
  │
  │   if (authContext()->checkLogin() && !tenantContext()->has()):
  │       $firstTenant = $userLogin->tenants()->first();
  │       if ($firstTenant) tenantContext()->setId($firstTenant->id);
  │
  ▼
[chooseTenant] (ChooseCurrentTenant) — chỉ route có khai báo
  │
  │   $tenantId = tenantContext()->getId();
  │   if (!$tenantId) abort(403, 'Not Found Current Tenan');
  │
  ▼
Controller
```

### Các trường hợp thực tế

| Tình huống | Kết quả |
|---|---|
| User mới đăng nhập, chưa từng có session tenant, đã có ≥1 tenant | `SetDefaultTenant` tự set tenant đầu tiên → request đi tiếp bình thường |
| User mới đăng nhập, **chưa có tenant nào** | `SetDefaultTenant` không set được gì → nếu route có `chooseTenant` → **403** |
| User đã có `current_tenant_id` trong session | `SetDefaultTenant` skip (vì `tenantContext()->has()` = true) |
| User vào `/admin/tenant` (không có `chooseTenant`) | Luôn vào được, kể cả chưa có tenant |

---

## 4. `TenantContext` — Session-based, không phải dependency injection thông thường

File: [`app/Shared/Tenant/TenantContext.php`](../../app/Shared/Tenant/TenantContext.php)

```php
class TenantContext {
    private const string SESSION_KEY = 'current_tenant_id';

    public function getId(): int   { return session(self::SESSION_KEY); }
    public function setId(int $tenantId): void { session()->put(self::SESSION_KEY, $tenantId); }
    public function has(): bool    { return session()->has(self::SESSION_KEY); }
    public function forget(): void { session()->forget(self::SESSION_KEY); }
}
```

- Bind là `singleton` trong `AppServiceProvider::boot()`.
- Helper toàn cục `tenantContext()` (định nghĩa trong [`app/Shared/helpers.php`](../../app/Shared/helpers.php)) trả về instance từ container — dùng được ở **Controller**, **Middleware**, **Blade**, nhưng **KHÔNG dùng trong UseCase** (theo CLAUDE.md rule).

### Tenant Switching Flow

```
POST /admin/tenant/{id}/switch
  → TenantController::switchTenant($tenantId)
       → changeTenantSelectedUseCase->execute($userId, $tenantId)   // validate user thuộc tenant này
       → tenantContext()->setId($tenantId)                          // ghi session NGAY SAU KHI useCase OK
       → redirect()->route('dashboard')
```

Lưu ý: việc ghi session nằm ở **Controller**, không phải UseCase — đúng pattern.

---

## 5. `AuthContext` — Wrapper quanh `auth()`

File: [`app/Shared/Auth/AuthContext.php`](../../app/Shared/Auth/AuthContext.php)

```php
class AuthContext {
    public function getUser(): User { return auth()->user(); }
    public function getId(): int    { return auth()->id(); }
    public function checkLogin(): bool { return auth()->check(); }
}
```

Cũng là singleton, dùng qua helper `authContext()`. Dùng trong Controller để lấy `userId`/`actorName` truyền tường minh vào UseCase — **đúng pattern** vì UseCase nhận `int $userId` / `string $actorName`, không tự gọi `auth()`.

---

## 6. `TenantScope` — Global Scope (model `Tenant`)

File: [`app/Models/Scopes/TenantScope.php`](../../app/Models/Scopes/TenantScope.php)

```php
class TenantScope implements Scope {
    public function apply(Builder $builder, Model $model): void {
        if (authContext()->checkLogin()) {
            $builder->whereHas('users', function ($q) {
                $q->where('users.id', authContext()->getId());
            });
        }
    }
}
```

**Quan trọng — dễ hiểu nhầm:**
- Scope này áp lên model **`Tenant`**, lọc ra **các tenant mà user hiện tại là thành viên** (qua bảng pivot `tenant_user`). Nó **KHÔNG** liên quan đến `session('current_tenant_id')`.
- Với `Project`, `Task`, `User`, `Notification`... việc lọc theo `tenant_id` của workspace hiện tại được làm **tường minh** trong từng Repository (`WHERE tenant_id = ?`), nhận `tenantId` truyền từ `tenantContext()->getId()` ở Controller → UseCase → Repository.
- ⇒ "Multi-tenant isolation" trong hệ thống này là **2 lớp độc lập**:
  1. `TenantScope` — đảm bảo user chỉ thấy/switch được các `Tenant` (workspace) mà họ là thành viên.
  2. Repository tường minh `tenant_id = ?` — đảm bảo dữ liệu nghiệp vụ (Project/Task/...) chỉ thuộc workspace đang chọn.

---

## 7. Tóm tắt: Một request điển hình `GET /admin/task`

```
1. auth middleware        → user đã login (session)
2. SetDefaultTenant        → session đã có 'current_tenant_id' → skip
3. chooseTenant             → tenantContext()->getId() = 3 → OK, tiếp tục
4. TaskController::index()
     → tenantId = tenantContext()->getId()  // = 3
     → getTasksUseCase->execute($tenantId)
          → taskRepository->getAllByTenant(3)   // WHERE tenant_id = 3
     → return view('admin.pages.task.index', compact('tasks'))
```

Xem chi tiết flow CRUD qua các layer ở [02-feature-crud-flow.md](./02-feature-crud-flow.md).
