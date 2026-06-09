# Notification System — Architecture

**Status:** Planning  
**Last Updated:** 2026-06-09

---

## 1. Design Decisions

### Pattern: Giống Mail Service, không phải Laravel Notification

Laravel có built-in `Notification` facade. Tuy nhiên project này **không dùng** vì:

| | Laravel Notification | Custom NotificationService |
|---|---|---|
| Clean Architecture | ❌ Eloquent trong UseCase | ✅ Interface-based |
| Multi-tenant scope | ❌ Phải tự add | ✅ Built-in |
| Config per event | ❌ Hard | ✅ `config/notification.php` |
| Test isolation | ⚠️ Fake facade | ✅ NullNotificationService |
| Consistency với project | ❌ Different pattern | ✅ Giống AuditLogger, MailService |

**Quyết định:** Dùng `NotificationServiceInterface` pattern — consistent với cách project đã làm với AuditLogger và MailService.

### Polling thay vì WebSocket

- MVP không cần real-time millisecond
- Livewire `wire:poll.5s` là đủ — đơn giản, không cần Redis Pub/Sub, không cần Laravel Echo
- Có thể nâng lên SSE hoặc WebSocket sau nếu cần

---

## 2. Layer Mapping

```
Domain Layer
├── Entities/NotificationEntity.php
└── Repositories/NotificationRepositoryInterface.php

Application Layer
├── Contracts/NotificationServiceInterface.php
├── Contracts/NotificationHandlerInterface.php
└── DTOs/NotificationDTO.php

Infrastructure Layer
├── Notifications/NotificationService.php          (implements interface)
├── Notifications/NullNotificationService.php      (tests)
├── Notifications/Handlers/
│   ├── TaskAssignedHandler.php
│   ├── TaskStatusChangedHandler.php
│   ├── TenantMemberAddedHandler.php
│   ├── TenantMemberRemovedHandler.php
│   └── TenantRoleChangedHandler.php
├── Notifications/Jobs/WriteNotificationJob.php
├── Persistence/Repositories/EloquentNotificationRepository.php
└── Console/Commands/CleanupOldNotificationsCommand.php

Models
└── Notification.php (Eloquent)

Http/Presentation Layer
└── resources/views/components/notification-bell.blade.php  (Livewire Volt)

Config
└── config/notification.php
```

---

## 3. System Overview

```
UseCase (e.g. AssignTaskUseCase)
        │
        │  $this->notificationService->notifyOne('task.assigned', $tenantId, $assigneeId, [...])
        ▼
NotificationServiceInterface
        │
        │  dispatch job to queue
        ▼
WriteNotificationJob (queue: 'notifications', tries=3, backoff=60)
        │
        │  resolve handler from config
        │  handler->handle(tenantId, context) → NotificationDTO
        ▼
EloquentNotificationRepository
        │
        │  insert into notifications table
        ▼
notifications table
        │
        │  Livewire polling (every 5s)
        ▼
notification-bell component
        │
        ├── badge: unread count
        └── dropdown: 10 latest
```

---

## 4. Class Diagram

```
NotificationServiceInterface
  + notify(event, tenantId, recipientIds[], context): void
  + notifyOne(event, tenantId, userId, context): void

NotificationService (implements interface)
  - repository: NotificationRepositoryInterface
  + notify(...): void   → dispatch WriteNotificationJob per recipient
  + notifyOne(...): void

NullNotificationService (implements interface)
  - sent: array
  + assertNotified(event, userId): bool
  + reset(): void

NotificationHandlerInterface
  + handle(tenantId, context): NotificationDTO

NotificationDTO
  + event: string
  + recipientIds: int[]
  + title: string
  + body: ?string
  + url: ?string
  + data: array

WriteNotificationJob (ShouldQueue)
  + handle(NotificationHandlerInterface $handler, NotificationRepositoryInterface $repo)

NotificationRepositoryInterface
  + createForUser(dto, userId, tenantId): NotificationEntity
  + getUnreadByUser(userId, tenantId, limit): array
  + countUnreadByUser(userId, tenantId): int
  + markAsRead(notificationId, userId): void
  + markAllAsRead(userId, tenantId): void
```

---

## 5. Data Flow: task.assigned

```
1. AssignTaskUseCase::execute()
       │
       └── notificationService->notifyOne(
               'task.assigned',
               $tenantId,
               $assigneeId,
               ['task_id' => $task->id, 'task_title' => $task->title, 'actor_name' => $actorName]
           )

2. NotificationService::notifyOne()
       │
       └── WriteNotificationJob::dispatch(
               event: 'task.assigned',
               tenantId: $tenantId,
               userId: $assigneeId,
               context: [...]
           )->onQueue('notifications')

3. WriteNotificationJob::handle()
       │
       ├── resolve TaskAssignedHandler from config
       ├── $dto = handler->handle($tenantId, $context)
       │     └── title: "Alice assigned you \"Fix login bug\""
       │         url:   route('task.show', $task_id)
       │         data:  ['task_id' => 5, 'actor_name' => 'Alice']
       │
       └── repository->createForUser($dto, $userId, $tenantId)

4. Livewire notification-bell (polling 5s)
       │
       ├── countUnreadByUser() → badge number
       └── getUnreadByUser()   → dropdown items
```

---

## 6. Livewire Component: NotificationBell

```
notification-bell (Livewire Volt, wire:poll.5s)
  state:
    - notifications: array  (10 latest)
    - unreadCount: int
    - isOpen: bool

  mount():
    load notifications + count

  poll() [every 5s]:
    refresh count only (cheap query)

  open():
    isOpen = true
    load full list

  markRead(id):
    repository->markAsRead(id, auth user)
    refresh list

  markAllRead():
    repository->markAllAsRead(auth user, current tenant)
    refresh
```

**Polling strategy:** Poll unread count mỗi 5s (chỉ 1 COUNT query). Load full list chỉ khi user click mở dropdown → không lãng phí bandwidth.

---

## 7. Multi-Tenant Scoping

Notification được scope theo `tenant_id` **và** `user_id`:

```
Khi user switch tenant:
  → TenantContext thay đổi
  → notification-bell re-mount với tenantId mới
  → Badge và dropdown chỉ show notification của tenant mới

Một user thuộc 2 tenants:
  → Tenant A: 3 unread
  → Tenant B: 5 unread
  → Badge chỉ show số của tenant đang active
```

Handler resolve recipients từ DB — không nhận từ caller:

```php
// TaskAssignedHandler
public function handle(int $tenantId, array $context): NotificationDTO
{
    // context chứa assignee_id — truyền từ UseCase
    return new NotificationDTO(
        event:        'task.assigned',
        recipientIds: [$context['assignee_id']],
        title:        "{$context['actor_name']} assigned you \"{$context['task_title']}\"",
        url:          route('task.show', $context['task_id']),
        data:         $context,
    );
}
```

---

## 8. Cleanup Strategy

`CleanupOldNotificationsCommand` (`notification:cleanup`):
- Chạy hàng ngày qua scheduler
- Xóa notification `created_at < now() - 30 days`
- Xóa theo `tenant_id` batch để tránh lock bảng
- Log số records đã xóa

---

## 9. Integration với UseCase hiện có

Các UseCase cần inject thêm `NotificationServiceInterface`:

| UseCase | Event | Khi nào |
|---|---|---|
| `AssignTaskUseCase` (cần tạo) | `task.assigned` | Sau khi assign |
| `UpdateTaskUseCase` | `task.status_changed` | Khi status thay đổi |
| `AttachUserToTenantUseCase` (cần tạo) | `tenant.member_added` | Sau khi attach |
| `DetachUserFromTenantUseCase` (cần tạo) | `tenant.member_removed` | Trước khi detach |
| `ChangeUserRoleUseCase` (cần tạo) | `tenant.role_changed` | Sau khi đổi role |

> `CreateTenantUseCase` đã inject `MailServiceInterface`. Thêm `NotificationServiceInterface` theo cùng pattern.
