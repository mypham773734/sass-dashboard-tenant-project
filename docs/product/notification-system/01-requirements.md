# Notification System — Requirements

**Status:** Planning  
**Level:** Developer reading  
**Purpose:** What notifications are, what data they need

---

## 🎯 Design Decision: Custom Service vs Laravel Notification

**Quick answer:** We build custom `NotificationServiceInterface` instead of using Laravel's built-in because:

| Need | Laravel | Ours |
|---|---|---|
| Keep UseCase clean (no Eloquent) | ❌ | ✅ |
| Multi-tenant support | ❌ | ✅ |
| Config-driven (add events via config) | ❌ | ✅ |
| Flexible recipients (query from DB) | ❌ | ✅ |
| Easy testing | ❌ | ✅ |

👉 **Deep dive:** [02-architecture.md](./02-architecture.md#1-design-decisions) (optional read)

---

## 📋 What Events Do We Support? (MVP)

| Event | When | Who Gets It | Priority |
|---|---|---|---|
| `task.assigned` | User assigned to task | Assignee | 🔴 High |
| `task.status_changed` | Task status changed | Creator + Assignee | 🟡 Medium |
| `tenant.member_added` | New user joins workspace | Admins | 🟡 Medium |
| `tenant.member_removed` | User kicked from workspace | Admins + kicked user | 🔴 High |
| `tenant.role_changed` | User role changes (admin→member) | Affected user | 🔴 High |

**Phase 2 (later):** mentions, project created, project deleted, invitation accepted

---

## 🗄️ Data: What's a Notification?

### Database Table: `notifications`

```
id           → unique ID (1, 2, 3, ...)
user_id      → who receives this (recipient)
tenant_id    → which workspace (for multi-tenant scoping)
event        → what happened ('task.assigned', 'tenant.member_removed', ...)
title        → human-readable text ("Alice assigned you Task #5")
body         → optional extra detail ("Due tomorrow")
url          → where to go when clicked (route('task.show', 5))
is_read      → true/false (read or unread)
read_at      → when marked as read (timestamp)
data         → extra JSON (task_id, actor_name, ...) for reference
created_at   → when created
updated_at   → when last read
```

### Example Row

```php
[
  'id'        => 42,
  'user_id'   => 5,           // Alice
  'tenant_id' => 3,           // Acme Corp
  'event'     => 'task.assigned',
  'title'     => 'Bob assigned you "Fix login bug"',
  'body'      => null,
  'url'       => '/tasks/123',
  'is_read'   => false,
  'read_at'   => null,
  'data'      => json_encode(['task_id' => 123, 'actor_name' => 'Bob']),
  'created_at' => '2026-06-10 14:30:00',
]
```

---

## 🔧 How Code Sends Notifications

### API: NotificationServiceInterface

```php
interface NotificationServiceInterface {
    
    // Notify ONE user
    public function notifyOne(
        string $event,              // 'task.assigned'
        int $tenantId,              // 3
        int $userId,                // 5
        array $context = []         // ['task_id' => 123, 'task_title' => '...']
    ): void;

    // Notify MULTIPLE users
    public function notify(
        string $event,              // 'tenant.member_removed'
        int $tenantId,              // 3
        array $recipientIds,        // [1, 2, 4]  (admin user IDs)
        array $context = []
    ): void;
}
```

### Real Code Example

```php
// In UpdateTaskUseCase
class UpdateTaskUseCase {
    public function __construct(
        private readonly NotificationServiceInterface $notificationService,
        private readonly TaskRepositoryInterface $repository,
    ) {}

    public function execute(int $taskId, UpdateTaskDTO $dto, int $tenantId): TaskEntity
    {
        $oldTask = $this->repository->findById($taskId);
        $newTask = $this->repository->update($taskId, $dto);

        // Notify assignee if status changed
        if ($oldTask->status !== $newTask->status && $newTask->assigneeId) {
            $this->notificationService->notifyOne(
                event:    'task.status_changed',
                tenantId: $tenantId,
                userId:   $newTask->assigneeId,
                context:  [
                    'task_id'     => $newTask->id,
                    'task_title'  => $newTask->title,
                    'old_status'  => $oldTask->status,
                    'new_status'  => $newTask->status,
                    'actor_name'  => auth()->user()->name,  // who changed it
                ]
            );
        }

        return $newTask;
    }
}
```

---

## 🔌 Data Models (Domain Layer)

### NotificationEntity (Pure PHP, no Eloquent)

```php
class NotificationEntity {
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $event,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $url,
        public readonly bool $isRead,
        public readonly ?string $readAt,
        public readonly array $data,
        public readonly string $createdAt,
    ) {}
}
```

### NotificationRepositoryInterface

```php
interface NotificationRepositoryInterface {
    
    // Create (called by queue job)
    public function createForUser(
        CreateNotificationDTO $dto,
        int $userId,
        int $tenantId
    ): NotificationEntity;

    // Read (called by Livewire component)
    public function getUnreadByUser(int $userId, int $tenantId, int $limit = 10): array;
    public function countUnreadByUser(int $userId, int $tenantId): int;

    // Update
    public function markAsRead(int $notificationId, int $userId): void;
    public function markAllAsRead(int $userId, int $tenantId): void;

    // Delete old ones (cleanup job)
    public function deleteOlderThan(int $tenantId, Carbon $before): int;
}
```

---

## 📝 Context: What Data to Pass?

Each event type needs specific context. Pass it as array:

```php
// task.assigned context
[
    'task_id'     => 123,
    'task_title'  => 'Fix login bug',
    'actor_name'  => 'Alice',
    'assignee_id' => 5,
]

// tenant.member_removed context
[
    'removed_user_id'   => 7,
    'removed_user_name' => 'Bob',
    'actor_name'        => 'Alice',
]

// tenant.role_changed context
[
    'target_user_id'   => 5,
    'target_user_name' => 'Bob',
    'old_role'         => 'admin',
    'new_role'         => 'member',
    'actor_name'       => 'Alice',
]
```

---

## ✅ Functional Requirements (What Must Work)

- [ ] FR1: Config can enable/disable each event type via `.env`
- [ ] FR2: Handler resolves title and URL from context
- [ ] FR3: Simple events use GenericHandler (from config template)
- [ ] FR4: Complex events use BaseHandler (DB queries for recipients)
- [ ] FR5: Notifications saved to DB asynchronously (queue)
- [ ] FR6: Bell icon shows unread count
- [ ] FR7: Dropdown shows 10 latest notifications
- [ ] FR8: Click notification → mark read + go to resource
- [ ] FR9: Notifications scoped to current tenant
- [ ] FR10: Can mark single as read, or mark all read

---

## 🚀 Non-Functional Requirements (Performance, Quality)

| Requirement | Target |
|---|---|
| **Queue latency** | Write notification ≤ 5ms (async via queue) |
| **Unread count query** | ≤ 10ms (use index) |
| **Test isolation** | Use `NullNotificationService` (no DB hit in tests) |
| **Retry logic** | 3 retries with 60s backoff if job fails |
| **Auto-cleanup** | Delete notifications > 30 days old daily |
| **Multi-tenant safety** | User can only see notifications for current tenant |

---

## 🚫 Out of Scope (We DON'T do this)

- **Real-time WebSocket** — Polling with Livewire is enough
- **Push notifications (mobile)** — No mobile app yet
- **Email fallback** — Mail Service handles that separately
- **Notification preferences per user** — Phase 2
- **Grouping** — "5 tasks assigned to you" vs 5 separate → Phase 2

---

## 🔄 Multi-Tenant Scoping

**Rule:** User can only see notifications for their current tenant

```php
// User switches tenant via dropdown
// ↓
// Livewire component re-mounts
// ↓
// Queries notifications WHERE user_id=5 AND tenant_id=3  (new tenant)
// ↓
// Badge and dropdown show only notifications for tenant 3
```

---

## 🧪 Success Criteria (Test These)

- [ ] When task assigned → notification created in DB
- [ ] Unread count correct: 3 unread → badge shows "3"
- [ ] Click notification → user redirected to correct resource
- [ ] Mark all as read → all notifications updated is_read=true
- [ ] Switch tenant → see only that tenant's notifications
- [ ] Disable event in .env → no notifications created
- [ ] Test with `NullNotificationService` → no DB queries

---

## 📚 Next Step

Read [02-architecture.md](./02-architecture.md) to understand:
- How handlers work
- GenericHandler vs BaseHandler difference
- How data flows through system
