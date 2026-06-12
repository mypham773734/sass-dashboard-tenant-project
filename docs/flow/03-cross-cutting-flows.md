# Flow 3: Cross-Cutting Concerns — Audit, Mail, Notification

**Phạm vi:** 3 hệ thống "side-effect" được gọi từ bên trong UseCase sau khi thao tác chính hoàn tất. Cả 3 đều theo chung 1 pattern kiến trúc nhưng phục vụ mục đích khác nhau.

---

## 0. Pattern chung

```
UseCase
  └─ inject Interface (Application layer)
        └─ Infrastructure impl
              └─ dispatch Job (queue)
                    └─ Job::handle() → ghi DB / gửi mail / broadcast
```

| | Audit | Mail | Notification |
|---|---|---|---|
| Interface | `AuditLoggerInterface` | `MailServiceInterface` | `NotificationServiceInterface` |
| Impl (prod) | `QueuedAuditLogger` | `MailService` | `NotificationService` |
| Impl (test) | `NullAuditLogger` | `NullMailService` | `NullNotificationService` |
| Job | `WriteAuditLogJob` | `SendMailJob` | `WriteNotificationJob` |
| Bảng DB | `audit_logs` | *(không lưu, gửi trực tiếp)* | `notifications` |
| Trigger thêm | Event listener (`auth.login/logout/failed`) | Scheduled command (`mail:send-scheduled`) | — |
| Output cuối | Trang `/admin/audit` | Email | Bell icon (real-time qua Echo) |

Tất cả đều bind trong `AppServiceProvider::boot()` và đều **queue-based** (non-blocking).

---

## 1. Audit Logging

### Trigger #1 — Từ UseCase (sau khi thao tác CRUD)

```php
// CreateTaskUseCase::notifySystem()
$this->audit->log(
    action: 'task.created',
    entityId: $task->id,
    entityType: 'Task',
    newValues: ['title' => ..., 'status' => ..., 'project_id' => ..., 'assignee_id' => ...],
);

// UpdateTaskUseCase::writeLogs()
$this->audit->log(
    action: 'task.updated',
    entityId: $task->id,
    entityType: 'Task',
    newValues: [...],
    oldValues: [...],   // ← cho phép diff trước/sau
);
```

### Trigger #2 — Từ Event Listener (login/logout, KHÔNG qua UseCase)

File: [`app/Http/Listeners/AuthAuditListener.php`](../../app/Http/Listeners/AuthAuditListener.php), đăng ký trong `AppServiceProvider`:

```php
Event::listen(Login::class,  [AuthAuditListener::class, 'handleLogin']);
Event::listen(Failed::class, [AuthAuditListener::class, 'handleFailed']);
Event::listen(Logout::class, [AuthAuditListener::class, 'handleLogout']);
```

→ Đây là **lớp duy nhất** trong hệ thống ghi audit mà KHÔNG đi qua `AuditLoggerInterface`/UseCase — gọi thẳng `WriteAuditLogJob::dispatch([...])`. Lý do: sự kiện auth xảy ra ở tầng framework (Laravel Auth events), không có UseCase tương ứng.

### `QueuedAuditLogger` — đọc `tenantContext()` trực tiếp

```php
class QueuedAuditLogger implements AuditLoggerInterface {
    public function log(...): void {
        if (!config('audit.enabled', true)) return;

        $tenantId = tenantContext()->getId();   // ← đọc session ở đây

        WriteAuditLogJob::dispatch([
            'tenant_id' => $tenantId,
            'user_id'   => auth()->id(),
            'action'    => $action,
            ...
        ]);
    }
}
```

**Lưu ý quan trọng:** UseCase gọi `$this->audit->log(...)` **không truyền `tenantId`** — `QueuedAuditLogger` (Infrastructure layer) tự đọc `tenantContext()->getId()`. Điều này **không vi phạm** rule "UseCase không đọc session", vì việc đọc session nằm ở Infrastructure (được phép). Tuy nhiên cũng có nghĩa: nếu `audit->log()` được gọi từ context không có session tenant hợp lệ (vd: console command, queue job khác), `tenant_id` sẽ là `null`/lỗi — cần lưu ý khi viết code chạy ngoài HTTP request.

### Đọc lại Audit Log

```
GET /admin/audit  (middleware: chooseTenant)
  → AuditController::index()
       → GetAuditLogsUseCase::execute($tenantId)
            → AuditRepositoryInterface::getRecentByTenant($tenantId, ...)
       → view('admin.pages.audit.index')
```

---

## 2. Mail Service

### Trigger — Từ Tenant UseCases (settings/security events)

```php
// CreateTenantUseCase / UpdateTenantUseCase / DeleteTenantUseCase
$this->mailService->dispatch(
    type: 'tenant_notification',
    tenantId: $tenantId,
    context: ['event_type' => 'settings_changed', 'actor_name' => $actorName, ...]
);
```

`MailServiceInterface::dispatch()` → resolve `EmailHandlerInterface` tương ứng (config-driven, giống Notification) → `SendMailJob::dispatch($type, $tenantId, $context)` → queue → `MailService::send()` → render Blade (`resources/views/emails/*.blade.php`) → gửi mail tới admin/owner của tenant (resolve email từ DB tại runtime, KHÔNG hardcode trong config).

### Trigger — Scheduled (Audit Digest)

```php
// bootstrap/app.php
$schedule->command(SendScheduledEmailsCommand::class)->everyMinute();

// SendScheduledEmailsCommand
$mailService->dispatchScheduled($now);
```

`AuditDigestHandler::shouldSend()` chỉ trả `true` khi `$now->format('H:i') === '08:00'` (lịch `daily_08_00`) → tổng hợp audit log 24h gần nhất theo từng tenant → gửi email digest cho admin.

**Thứ tự dispatch quan trọng (DeleteTenantUseCase):** mail `tenant_notification` (event_type: `security`) phải dispatch **TRƯỚC** `detachAllUsers()`, nếu không handler không tìm được admin để gửi.

---

## 3. Notification System (in-app, real-time)

Đã có doc chi tiết riêng tại [`docs/product/notification-system/04-flow-analysis.md`](../product/notification-system/04-flow-analysis.md). Tóm tắt nhanh:

```
UseCase.notifySystem()
  → notificationService->notifyOne('task.assigned', $tenantId, $userId, $context)
       → check config('notification.enabled') + per-event enabled
       → WriteNotificationJob::dispatch(...) → queue 'notifications'
            → resolve handler (Generic hoặc Base, config-driven)
            → ghi bảng `notifications`
            → broadcast(NotificationCreated) → PrivateChannel "tenant.{tenantId}.user.{userId}"
                 → NotificationBell (Livewire) nhận qua #[On('echo-private:...')] → refresh()
```

---

## 4. Một request có thể trigger CẢ 3 hệ thống cùng lúc

Ví dụ: `PUT /admin/task/{id}` đổi status task từ `todo` → `done` VÀ đổi assignee:

```
UpdateTaskUseCase::execute()
  ├─ taskRepository->update($entity)
  ├─ notifySystem()
  │    ├─ notificationService->notifyOne('task.status_changed', ...)  → bell + broadcast cho assignee
  │    └─ notificationService->notifyOne('task.assigned', ...)        → bell + broadcast cho assignee mới
  └─ writeLogs()
       └─ audit->log('task.updated', oldValues, newValues)            → ghi audit_logs
```

→ 3 job riêng biệt được dispatch vào queue (`notifications` x2, `audit`/default x1), độc lập với nhau — nếu 1 job fail (vd thiếu context), 2 job còn lại vẫn chạy bình thường (mỗi job có `tries=3, backoff=60s` riêng).

**Tenant settings (`PUT /admin/tenant/{slug}`)** thì trigger Audit + Mail (KHÔNG có Notification trong code hiện tại):
```
UpdateTenantUseCase::execute()
  ├─ tenantRepository->update(...)
  └─ mailService->dispatch('tenant_notification', tenantId, ['event_type' => 'settings_changed', ...])
```

---

## 5. Khi thêm 1 UseCase mới cần side-effect

| Cần gì? | Inject interface | Gọi gì |
|---|---|---|
| Ghi lịch sử thay đổi (xem ở `/admin/audit`) | `AuditLoggerInterface $audit` | `$audit->log(action, entityId, entityType, newValues, oldValues)` |
| Gửi email (settings, security, digest) | `MailServiceInterface $mailService` | `$mailService->dispatch(type, tenantId, context)` |
| Push thông báo trong app (bell icon, real-time) | `NotificationServiceInterface $notificationService` | `$notificationService->notifyOne(event, tenantId, userId, context)` hoặc `notify()` cho nhiều người |

Cả 3 đều **không throw exception** ra ngoài UseCase nếu queue/dispatch thất bại ngay lúc gọi (job retry nội bộ) — UseCase vẫn trả về kết quả chính (Entity) bình thường cho Controller.
