# Notification System — Phân tích Flow (Triển khai thực tế)

**Status:** Đã triển khai (xem [VERIFICATION.md](./VERIFICATION.md))
**Last Updated:** 2026-06-11
**Mục đích:** Phân tích end-to-end flow dựa trên code thực tế trong repo — dùng để debug, review, hoặc onboard dev mới.

---

## 1. Tổng quan kiến trúc đã triển khai

So với plan ban đầu ([03-implementation-plan.md](./03-implementation-plan.md)), bản triển khai thực tế có thêm **Realtime Broadcasting** (Laravel Echo + Private Channel) — không chỉ polling như dự kiến ban đầu.

```
UseCase
  │
  ▼
NotificationServiceInterface (notify / notifyOne)
  │
  ▼
WriteNotificationJob (queue: notifications, tries=3, backoff=60s)
  │
  ├─► Resolve Handler (config-driven)
  │     ├─ GenericNotificationHandler   (task.assigned, task.status_changed)
  │     └─ BaseNotificationHandler      (tenant.member_added/removed, role_changed)
  │
  ├─► NotificationRepository::createForUser() → ghi DB bảng `notifications`
  │
  └─► broadcast(NotificationCreated)  ──► Private Channel "tenant.{tenantId}.user.{userId}"
                                              │
                                              ▼
                                    Livewire NotificationBell
                                    (lắng nghe qua #[On('echo-private:...')])
                                              │
                                              ▼
                                    Bell icon + badge + dropdown
```

**Điểm khác biệt lớn nhất so với plan:** Bell component **không cần polling 5s** nữa — nó lắng nghe event broadcast real-time qua `echo-private` channel, gọi `refresh()` ngay khi có notification mới.

---

## 2. Flow chi tiết: `task.assigned` (Generic Handler)

### Bước 1 — Trigger từ UseCase

File: [`CreateTaskUseCase.php`](../../../app/Application/Task/UseCases/CreateTaskUseCase.php)

```
CreateTaskUseCase::execute()
  → repository->create(...)
  → notifySystem($task, $tenantId)
       if ($task->assigneeId):
           notificationService->notifyOne(
               event: 'task.assigned',
               tenantId: $tenantId,
               userId: $task->assigneeId,
               context: [task_id, task_title, assignee_id, actor_name]
           )
```

**Lưu ý:** `notifySystem()` gọi `authContext()->getUser()->name` để lấy `actor_name`. Đây là điểm cần chú ý vì theo rule chung của project, UseCase không nên đọc session/context trực tiếp — nhưng đây là code đã được team chỉnh sửa trực tiếp (đã verify ở VERIFICATION.md), không thuộc phạm vi thay đổi của doc này.

### Bước 2 — NotificationService dispatch job

File: [`NotificationService.php`](../../../app/Infrastructure/Notifications/NotificationService.php)

```
notifyOne(event, tenantId, userId, context)
  → check config('notification.enabled')        // global on/off
  → check config('notification.event_types.task.assigned.enabled')
  → context['__event__'] = 'task.assigned'        // gắn thêm event key
  → WriteNotificationJob::dispatch(...)->onQueue('notifications')
```

`__event__` được gắn vào context để `GenericNotificationHandler` biết đang xử lý event nào (vì handler này dùng chung cho nhiều event).

### Bước 3 — Queue Job xử lý

File: [`WriteNotificationJob.php`](../../../app/Infrastructure/Notifications/Jobs/WriteNotificationJob.php)

```
handle(NotificationRepositoryInterface $repo)
  1. resolveHandler()
       → config('notification.event_types.task.assigned.handler')
       → GenericNotificationHandler::class

  2. $dto = $handler->handle($tenantId, $context)
       → xem Bước 4

  3. $entity = $repo->createForUser(CreateNotificationDTO, userId, tenantId)
       → INSERT vào bảng `notifications`

  4. broadcast(new NotificationCreated(...))
       → gửi lên Private Channel "tenant.{tenantId}.user.{userId}"
```

### Bước 4 — GenericNotificationHandler render DTO

File: [`GenericNotificationHandler.php`](../../../app/Infrastructure/Notifications/Handlers/GenericNotificationHandler.php)

Đọc config của event `task.assigned`:

```php
'task.assigned' => [
    'recipients'     => 'assignee_id',
    'title_template' => '{actor_name} assigned you "{task_title}"',
    'url_template'   => 'task.show:{task_id}',
],
```

Xử lý:
1. **`renderTemplate()`** — regex `/{(\w+)}/` thay `{actor_name}` → `Alice`, `{task_title}` → `Fix login bug`
   → Title: `Alice assigned you "Fix login bug"`
2. **`resolveRecipients('assignee_id', $context)`** — vì là string (không phải array) → `[$context['assignee_id']]`
3. **`buildRoute('task.show:{task_id}', $context)`** — parse thành `route('task.show', $context['task_id'])` → `/tasks/123`

Kết quả: `NotificationDTO(event, recipientIds, title, body=null, url, data=$context)`

### Bước 5 — Ghi DB + Broadcast

`EloquentNotificationRepository::createForUser()` insert:

```sql
INSERT INTO notifications (tenant_id, user_id, event, title, url, data, is_read, created_at)
VALUES (3, 5, 'task.assigned', 'Alice assigned you "Fix login bug"', '/tasks/123', {...}, false, NOW())
```

Sau đó `broadcast(NotificationCreated)`:

File: [`NotificationCreated.php`](../../../app/Infrastructure/Notifications/Events/NotificationCreated.php)

```php
broadcastOn(): [new PrivateChannel("tenant.{$tenantId}.user.{$userId}")]
broadcastAs(): 'notification-created'
broadcastWith(): ['notification_id', 'title', 'body', 'url', 'created_at']
```

Channel name: `tenant.3.user.5` — **scoped theo cả tenant lẫn user**.

### Bước 6 — Authorization channel

File: [`routes/channels.php`](../../../routes/channels.php)

```php
Broadcast::channel('tenant.{tenantId}.user.{userId}', function ($user, $tenantId, $userId) {
    if ((int) $userId !== $user->id) return false;          // chỉ chính chủ mới subscribe được
    return $user->tenants()->wherePivot('tenant_id', $tenantId)->exists()
        ? $user : false;                                      // phải thuộc tenant đó
});
```

Hai lớp bảo vệ: (1) user_id trong channel name phải khớp user đang đăng nhập, (2) user phải có quan hệ với tenant_id đó trong bảng `tenant_user`.

### Bước 7 — Frontend nhận event

File: [`NotificationBell.php`](../../../app/Livewire/NotificationBell.php)

```php
#[On('echo-private:tenant.{tenantId}.user.{userId},notification-created')]
public function onNotificationCreated($data): void
{
    $this->refresh();
}
```

Livewire subscribe vào channel `tenant.{tenantId}.user.{userId}` (giá trị thực tế lấy từ property `$tenantId`, `$userId` của component, set trong `mount()`). Khi nhận event `notification-created` → gọi `refresh()`:

```php
refresh():
  unreadCount = repo->countUnreadByUser($userId, $tenantId)
  notifications = repo->getUnreadByUser($userId, $tenantId, 10)->map(...)
```

→ Bell icon cập nhật badge ngay lập tức (không cần đợi polling).

---

## 3. Flow chi tiết: `tenant.member_removed` (Base Handler — multi-recipient)

### Trigger

`DetachUserFromTenantUseCase::notifySystem()` gọi:

```php
notificationService->notify(
    event: 'tenant.member_removed',
    tenantId: $tenantId,
    recipientIds: [...adminIds, $removedUserId],   // nhiều người nhận
    context: [...]
)
```

### NotificationService::notify()

```php
foreach ($recipientIds as $userId) {
    $this->notifyOne($event, $tenantId, $userId, $context);
}
```

→ **Mỗi recipient = 1 job riêng** trong queue (N jobs cho N recipients).

### TenantMemberRemovedHandler (extends BaseNotificationHandler)

`BaseNotificationHandler::handle()` là `final` — không override được, chỉ override các hook:

```php
abstract resolveRecipients(tenantId, context): array
abstract renderTitle(context): string
renderBody(tenantId, context): ?string   // optional override
buildUrl(tenantId, context): string      // optional override
```

`assertContextComplete()` check `$requiredContext` (khai báo trong subclass) — nếu thiếu key sẽ throw `InvalidArgumentException` ngay trong job (sẽ retry 3 lần rồi vào failed_jobs).

**Khác biệt quan trọng:** Mặc dù `notify()` đã loop qua từng recipient và dispatch N job riêng, nhưng `resolveRecipients()` trong handler **vẫn được gọi lại** bên trong mỗi job (vì `BaseNotificationHandler::handle()` luôn set `recipientIds` vào DTO). Tuy nhiên `WriteNotificationJob` chỉ dùng `$this->userId` (từ job, không phải từ `$dto->recipientIds`) khi gọi `createForUser()` — nghĩa là `$dto->recipientIds` từ handler **không thực sự được dùng để ghi DB** trong flow hiện tại. Đây là điểm cần lưu ý nếu maintain code (recipientIds trong DTO chỉ mang tính metadata/tham chiếu).

---

## 4. Bảng tổng hợp Event → Handler → Recipients

| Event | Handler | Recipients resolve ở đâu | Trigger từ |
|---|---|---|---|
| `task.assigned` | GenericNotificationHandler | `context['assignee_id']` (config: `recipients: 'assignee_id'`) | CreateTaskUseCase, UpdateTaskUseCase (khi đổi assignee) |
| `task.status_changed` | GenericNotificationHandler | `context['creator_id']`, `context['assignee_id']` (config: array) | UpdateTaskUseCase (khi đổi status) |
| `tenant.member_added` | TenantMemberAddedHandler | Query DB — admins của tenant | AttachUserToTenantUseCase |
| `tenant.member_removed` | TenantMemberRemovedHandler | Query DB — admins + user bị xóa | DetachUserFromTenantUseCase |
| `tenant.role_changed` | TenantRoleChangedHandler | User bị đổi role | ChangeUserRoleUseCase |

> **Lưu ý:** Với `task.status_changed`, code hiện tại trong `UpdateTaskUseCase` chỉ gọi `notifyOne()` với `userId: $task->assigneeId` — nghĩa là **chỉ assignee nhận được**, mặc dù config khai báo `recipients: ['creator_id', 'assignee_id']`. Field `recipients` trong config dành cho `GenericNotificationHandler::resolveRecipients()` chỉ có ý nghĩa khi UseCase gọi `notify()` (multi-recipient) thay vì `notifyOne()`. Đây là gap giữa config và actual usage — cần đồng bộ nếu muốn creator cũng nhận thông báo.

---

## 5. Database Schema thực tế

Bảng `notifications` ([migration](../../../database/migrations/2026_06_10_044753_create_notifications_table.php)):

```
id, tenant_id, user_id, event, title, body, url, is_read, read_at, data (json), created_at, updated_at

Indexes:
  (user_id, tenant_id, is_read)      → đếm unread nhanh
  (user_id, tenant_id, created_at)   → query dropdown 10 mới nhất
  (tenant_id)                        → cleanup theo tenant
```

**Quan trọng:** Model `Notification` **không dùng Global TenantScope** — mọi query đều pass `tenant_id` tường minh qua `NotificationRepositoryInterface`. Tránh side-effect khi `CleanupOldNotificationsCommand` chạy ngoài HTTP context (không có `tenantContext()`).

---

## 6. Testing & Failure Handling

| Cơ chế | Chi tiết |
|---|---|
| `NullNotificationService` | Bind trong test để không hit DB/queue. Có `assertNotified(event, userId)`, `getSent()`, `reset()` |
| Job retry | `tries = 3`, `backoff = 60s` — nếu handler throw (ví dụ thiếu context, hoặc không tìm thấy admin) thì retry trước khi vào `failed_jobs` |
| Event enable/disable | `config('notification.enabled')` (global) + `config('notification.event_types.{event}.enabled')` (per-event) — check ở `NotificationService` **trước khi** dispatch job |
| Cleanup | `notification:cleanup --days=30` — chạy theo từng tenant, xóa `created_at < now() - N days` |

---

## 7. Checklist khi thêm event mới

### Trường hợp đơn giản (1 recipient từ context, title cố định)
1. Thêm entry vào `config/notification.php` dùng `GenericNotificationHandler`
2. Gọi `notificationService->notifyOne()` trong UseCase với context đủ field cho `title_template` / `url_template`
3. Không cần code thêm

### Trường hợp phức tạp (cần query DB để tìm recipients)
1. Tạo class extends `BaseNotificationHandler`, implement `resolveRecipients()` + `renderTitle()`
2. Khai báo `protected array $requiredContext = [...]` để validate context đầu vào
3. Đăng ký handler trong `config/notification.php`
4. Gọi `notificationService->notify()` (multi) hoặc `notifyOne()` (single) trong UseCase

### Cả hai trường hợp
- Channel broadcast tự động hoạt động — không cần sửa `NotificationCreated` hay `routes/channels.php` (đã generic theo `tenant.{tenantId}.user.{userId}`)
- Nếu cần email song song → xem [Mail Service](../mail-service/readme.md), gọi thêm `mailService->dispatch()` — hai hệ thống độc lập, không phụ thuộc nhau
