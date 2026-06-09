# Notification System — Requirements

**Status:** Planning  
**Last Updated:** 2026-06-09

---

## 1. Event Types (MVP)

### Priority Levels

- **High** — hành động ảnh hưởng trực tiếp đến user hoặc security
- **Medium** — thay đổi trong workspace user đang làm việc
- **Low** — thông tin background, không cần action ngay

### Event Table

| Event Key | Trigger | Recipients | Priority | Link đến |
|---|---|---|---|---|
| `task.assigned` | User được assign vào task | Assignee | High | Task detail |
| `task.status_changed` | Trạng thái task thay đổi | Creator + Assignee | Medium | Task detail |
| `task.mentioned` | User được mention trong description/comment | User được mention | High | Task detail |
| `project.created` | Project mới được tạo | Owner + Admins của tenant | Low | Project detail |
| `project.deleted` | Project bị xóa | Owner + Admins | Medium | Tenant dashboard |
| `tenant.member_added` | User mới join workspace | Owner + Admins | Medium | User list |
| `tenant.member_removed` | User bị remove khỏi workspace | Owner + Admins + User bị remove | High | Tenant dashboard |
| `tenant.role_changed` | Role của user trong tenant thay đổi | User bị đổi role + Owner | High | Profile |
| `tenant.invitation_accepted` | User chấp nhận lời mời | Người mời | Medium | User list |

> **Phase 1 (MVP):** Triển khai `task.assigned`, `task.status_changed`, `tenant.member_added`, `tenant.member_removed`, `tenant.role_changed`.  
> **Phase 2:** `mention`, `project.created`, `project.deleted`, `tenant.invitation_accepted`.

---

## 2. Data Model

### `notifications` table

```
id                bigint PK
tenant_id         bigint FK → tenants (tenant scoping)
user_id           bigint FK → users   (recipient)
event             string               (e.g. 'task.assigned')
title             string               (rendered text, e.g. "Alice assigned you a task")
body              string nullable      (optional extra detail)
url               string nullable      (deeplink vào resource)
is_read           boolean default false
read_at           timestamp nullable
data              json nullable        (context payload — task_id, actor_name, ...)
created_at        timestamp
updated_at        timestamp
```

**Indexes cần thiết:**
- `(user_id, tenant_id, is_read)` — query unread count
- `(user_id, tenant_id, created_at DESC)` — query dropdown list
- `(tenant_id)` — cleanup job theo tenant

**Không có `updated_at` logic:** Notification không được edit, chỉ flip `is_read`.

---

## 3. Notification Entity (Domain)

```
NotificationEntity
  id:        int
  tenantId:  int
  userId:    int
  event:     string
  title:     string
  body:      ?string
  url:       ?string
  isRead:    bool
  readAt:    ?string
  data:      ?array
  createdAt: string
```

---

## 4. Repository Interface

```
NotificationRepositoryInterface
  createForUser(CreateNotificationDTO $dto, int $userId, int $tenantId): NotificationEntity
  getUnreadByUser(int $userId, int $tenantId, int $limit = 10): array
  countUnreadByUser(int $userId, int $tenantId): int
  markAsRead(int $notificationId, int $userId): void
  markAllAsRead(int $userId, int $tenantId): void
  deleteOlderThan(int $tenantId, Carbon $before): int
```

---

## 5. NotificationServiceInterface (Application Layer)

Cùng pattern với `MailServiceInterface` và `AuditLoggerInterface`:

```
NotificationServiceInterface
  notify(string $event, int $tenantId, array $recipientIds, array $context = []): void
  notifyOne(string $event, int $tenantId, int $userId, array $context = []): void
```

- `notify()` — gửi đến nhiều recipients (e.g. tất cả admin của tenant)
- `notifyOne()` — shorthand gửi đến 1 user (e.g. assignee)
- Cả hai đều **dispatch job vào queue**, không ghi DB trực tiếp

### NullNotificationService (Tests)

```
NullNotificationService
  assertNotified(string $event, int $userId): bool
  assertNotNotified(string $event): bool
  getAll(): array
  reset(): void
```

---

## 6. NotificationHandlerInterface

Mỗi event type có một handler riêng, cùng pattern với `EmailHandlerInterface`:

```
NotificationHandlerInterface
  handle(int $tenantId, array $context): NotificationDTO
```

`NotificationDTO` chứa:
```
event:        string
recipientIds: int[]
title:        string
body:         ?string
url:          ?string
data:         array
```

**Handler có trách nhiệm:**
- Resolve recipients từ DB (không nhận từ caller)
- Render `title` và `body` từ context
- Tạo `url` deeplink đến resource

---

## 7. Config (`config/notification.php`)

```php
return [
    'enabled' => env('NOTIFICATION_ENABLED', true),
    'queue'   => env('NOTIFICATION_QUEUE', 'notifications'),

    'event_types' => [
        'task.assigned' => [
            'enabled' => env('NOTIFICATION_TASK_ASSIGNED', true),
            'handler' => TaskAssignedNotificationHandler::class,
        ],
        'task.status_changed' => [
            'enabled' => env('NOTIFICATION_TASK_STATUS_CHANGED', true),
            'handler' => TaskStatusChangedNotificationHandler::class,
        ],
        'tenant.member_added' => [...],
        'tenant.member_removed' => [...],
        'tenant.role_changed' => [...],
    ],
];
```

---

## 8. Functional Requirements

| ID | Requirement |
|---|---|
| FR-01 | Bell icon header hiển thị badge số unread (0 không hiện badge) |
| FR-02 | Dropdown hiển thị tối đa 10 notification gần nhất, mới nhất ở trên |
| FR-03 | Click vào notification → mark as read + redirect đến `url` |
| FR-04 | "Mark all as read" button trong dropdown |
| FR-05 | Notification scoped theo `tenant_id` — chỉ thấy notification của tenant đang active |
| FR-06 | Notification của tenant khác không hiển thị khi switch tenant |
| FR-07 | Ghi notification qua queue — không block HTTP request |
| FR-08 | Có thể bật/tắt từng event type qua `.env` |
| FR-09 | Notification cũ hơn 30 ngày được cleanup tự động |
| FR-10 | Unread count cập nhật mà không cần reload trang (Livewire polling) |

---

## 9. Non-Functional Requirements

| ID | Requirement |
|---|---|
| NFR-01 | Ghi notification không làm tăng latency HTTP request (async queue) |
| NFR-02 | Query unread count < 10ms (index trên `user_id, tenant_id, is_read`) |
| NFR-03 | `NullNotificationService` dùng trong tất cả tests — không ghi DB |
| NFR-04 | Retry tối đa 3 lần nếu job fail, backoff 60s |
| NFR-05 | Cleanup job chạy hàng ngày, giữ tối đa 30 ngày |

---

## 10. Out of Scope (không làm trong MVP)

- **Real-time WebSocket / Laravel Echo** — Polling là đủ cho MVP
- **Push notification (mobile)** — Không có app mobile
- **Email fallback** — Mail Service xử lý riêng, không gộp
- **Notification preferences per user** — Phase 2
- **Notification grouping** ("5 tasks assigned to you") — Phase 2
- **Read receipts / delivery status** — Phase 2
