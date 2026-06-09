# Notification System — Implementation Plan

**Status:** Planning  
**Last Updated:** 2026-06-09

---

## Tổng quan

| Phase | Nội dung | Ưu tiên |
|---|---|---|
| 1 | Foundation: Migration + Entity + Repository Interface | Must |
| 2 | Service Layer: Interface + NullService + Job + Config | Must |
| 3 | Handlers (5 event types MVP) | Must |
| 4 | Integration vào Use Cases | Must |
| 5 | Livewire UI: NotificationBell component | Must |
| 6 | Cleanup command + scheduler | Should |
| 7 | Tests | Must |

---

## Phase 1 — Foundation

**Mục tiêu:** Tạo DB table, Domain entity, Repository interface.

### Files cần tạo

```
database/migrations/xxxx_create_notifications_table.php
app/Domain/Notification/Entities/NotificationEntity.php
app/Domain/Notification/Repositories/NotificationRepositoryInterface.php
app/Models/Notification.php
app/Infrastructure/Persistence/Repositories/EloquentNotificationRepository.php
```

### Migration schema

```php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('event');
    $table->string('title');
    $table->text('body')->nullable();
    $table->string('url')->nullable();
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    $table->json('data')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'tenant_id', 'is_read']);
    $table->index(['user_id', 'tenant_id', 'created_at']);
    $table->index(['tenant_id', 'created_at']);
});
```

### Eloquent Model

```php
// app/Models/Notification.php
protected $fillable = ['tenant_id', 'user_id', 'event', 'title', 'body', 'url', 'is_read', 'read_at', 'data'];
protected $casts    = ['is_read' => 'boolean', 'data' => 'array', 'read_at' => 'datetime'];
```

> **Không dùng Global TenantScope** — Repository query luôn pass `tenant_id` tường minh. Tránh side-effect khi cleanup job chạy ngoài request context.

### AppServiceProvider binding

```php
$this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
```

---

## Phase 2 — Service Layer

**Mục tiêu:** Interface + NullService + Job + Config.

### Files cần tạo

```
app/Application/Notification/Contracts/NotificationServiceInterface.php
app/Application/Notification/Contracts/NotificationHandlerInterface.php
app/Application/Notification/DTOs/NotificationDTO.php
app/Application/Notification/DTOs/CreateNotificationDTO.php
app/Infrastructure/Notifications/NotificationService.php
app/Infrastructure/Notifications/NullNotificationService.php
app/Infrastructure/Notifications/Jobs/WriteNotificationJob.php
config/notification.php
```

### NotificationService logic

```php
public function notifyOne(string $event, int $tenantId, int $userId, array $context = []): void
{
    if (! config('notification.enabled', true)) return;
    if (! $this->isEventEnabled($event)) return;

    WriteNotificationJob::dispatch($event, $tenantId, $userId, $context)
        ->onQueue(config('notification.queue', 'notifications'));
}

public function notify(string $event, int $tenantId, array $recipientIds, array $context = []): void
{
    foreach ($recipientIds as $userId) {
        $this->notifyOne($event, $tenantId, $userId, $context);
    }
}
```

### WriteNotificationJob

```php
// tries = 3, backoff = 60
public function handle(NotificationRepositoryInterface $repo): void
{
    $handler = $this->resolveHandler($this->event);
    $dto     = $handler->handle($this->tenantId, $this->context);

    $repo->createForUser(
        new CreateNotificationDTO(
            event:  $dto->event,
            title:  $dto->title,
            body:   $dto->body,
            url:    $dto->url,
            data:   $dto->data,
        ),
        userId:   $this->userId,
        tenantId: $this->tenantId,
    );
}
```

### AppServiceProvider binding

```php
$this->app->bind(NotificationServiceInterface::class, NotificationService::class);
```

---

## Phase 3 — Handlers

**Mục tiêu:** Build 5 handler cho 5 event type MVP. Mỗi handler build riêng lẻ.

### Files cần tạo

```
app/Infrastructure/Notifications/Handlers/TaskAssignedHandler.php
app/Infrastructure/Notifications/Handlers/TaskStatusChangedHandler.php
app/Infrastructure/Notifications/Handlers/TenantMemberAddedHandler.php
app/Infrastructure/Notifications/Handlers/TenantMemberRemovedHandler.php
app/Infrastructure/Notifications/Handlers/TenantRoleChangedHandler.php
```

### Context schema per handler

| Handler | Required context |
|---|---|
| `TaskAssignedHandler` | `task_id`, `task_title`, `actor_name`, `assignee_id` |
| `TaskStatusChangedHandler` | `task_id`, `task_title`, `old_status`, `new_status`, `actor_name`, `creator_id`, `assignee_id` (nullable) |
| `TenantMemberAddedHandler` | `new_user_name`, `new_user_id`, `actor_name` — recipients = admins từ DB |
| `TenantMemberRemovedHandler` | `removed_user_name`, `removed_user_id`, `actor_name` — recipients = admins + user bị remove |
| `TenantRoleChangedHandler` | `target_user_id`, `target_user_name`, `old_role`, `new_role`, `actor_name` |

### Ví dụ: TaskAssignedHandler

```php
public function handle(int $tenantId, array $context): NotificationDTO
{
    return new NotificationDTO(
        event:        'task.assigned',
        recipientIds: [$context['assignee_id']],
        title:        "{$context['actor_name']} assigned you \"{$context['task_title']}\"",
        body:         null,
        url:          route('task.show', $context['task_id']),
        data:         $context,
    );
}
```

---

## Phase 4 — Integration vào Use Cases

**Mục tiêu:** Inject `NotificationServiceInterface` vào các UseCase phù hợp.

### Use Cases cần sửa / tạo mới

| UseCase | Trạng thái | Thay đổi |
|---|---|---|
| `UpdateTaskUseCase` | Đã có | Inject + notify `task.status_changed` khi status thay đổi |
| `AssignTaskUseCase` | Cần tạo | Tạo mới, notify `task.assigned` |
| `AttachUserToTenantUseCase` | Cần tạo | Tạo mới, notify `tenant.member_added` |
| `DetachUserFromTenantUseCase` | Cần tạo | Tạo mới, notify `tenant.member_removed` |
| `ChangeUserRoleUseCase` | Cần tạo | Tạo mới, notify `tenant.role_changed` |

### Pattern inject (giống Mail Service)

```php
class UpdateTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface         $repository,
        private readonly AuditLoggerInterface            $audit,
        private readonly NotificationServiceInterface    $notificationService,  // thêm
    ) {}

    public function execute(int $id, UpdateTaskDTO $dto, int $tenantId, int $actorId): TaskEntity
    {
        $old  = $this->repository->findById($id);
        $task = $this->repository->update($id, $dto);

        if ($old->status !== $task->status && $task->assigneeId) {
            $this->notificationService->notifyOne('task.status_changed', $tenantId, $task->assigneeId, [
                'task_id'     => $task->id,
                'task_title'  => $task->title,
                'old_status'  => $old->status,
                'new_status'  => $task->status,
                'actor_name'  => $dto->actorName,
                'creator_id'  => $task->createdBy,
                'assignee_id' => $task->assigneeId,
            ]);
        }

        return $task;
    }
}
```

---

## Phase 5 — Livewire UI

**Mục tiêu:** Bell icon + dropdown trong header, auto-refresh.

### Files cần tạo

```
resources/views/components/notification-bell.blade.php  (Livewire Volt)
```

### Component structure

```php
// mount(): load count + notifications for current tenant
// open():  toggle dropdown, load list
// markRead($id): mark single, refresh
// markAllRead(): mark all, refresh
// wire:poll.5s → refresh $unreadCount only
```

### Header integration

```blade
{{-- resources/views/admin/partials/header.blade.php --}}
<livewire:notification-bell />
```

### UI chi tiết

```
[🔔 3]  ← badge chỉ hiện khi unread > 0

Dropdown:
┌─────────────────────────────────────┐
│ Notifications          [Mark all ✓] │
├─────────────────────────────────────┤
│ ● Alice assigned you "Fix bug"  2m  │
│   task.assigned                     │
├─────────────────────────────────────┤
│   Bob joined Acme Corp          1h  │
│   tenant.member_added               │
├─────────────────────────────────────┤
│         [View all]                  │
└─────────────────────────────────────┘

● = unread (blue dot)
no dot = already read
```

---

## Phase 6 — Cleanup

**Mục tiêu:** Tự động xóa notification cũ hơn 30 ngày.

### Files cần tạo

```
app/Console/Commands/CleanupOldNotificationsCommand.php
```

### Signature

```
php artisan notification:cleanup {--days=30}
```

### Scheduler (bootstrap/app.php)

```php
$schedule->command(CleanupOldNotificationsCommand::class)->dailyAt('03:00');
```

---

## Phase 7 — Tests

**Mục tiêu:** Cover tất cả critical paths.

### Files cần tạo

```
tests/Unit/Notification/NullNotificationServiceTest.php
tests/Unit/Notification/TaskAssignedHandlerTest.php
tests/Unit/Notification/TenantMemberRemovedHandlerTest.php
tests/Feature/Notification/WriteNotificationJobTest.php
tests/Feature/Notification/CleanupCommandTest.php
```

### Test cases quan trọng

| Test | Kiểm tra |
|---|---|
| `NullNotificationService::assertNotified` | record và query đúng |
| `NullNotificationService::reset` | clear state |
| `TaskAssignedHandler::handle` | title đúng, recipient đúng, url đúng |
| `TenantMemberRemovedHandler` | notify cả admins lẫn user bị remove |
| `WriteNotificationJob::handle` | ghi vào DB đúng event + user |
| `WriteNotificationJob::failed` | log error với context |
| `CleanupCommand` | xóa đúng records cũ, không xóa mới |
| `CountUnread` | trả về 0 sau markAllRead |

---

## File Checklist tổng hợp

```
Phase 1
[ ] database/migrations/xxxx_create_notifications_table.php
[ ] app/Domain/Notification/Entities/NotificationEntity.php
[ ] app/Domain/Notification/Repositories/NotificationRepositoryInterface.php
[ ] app/Models/Notification.php
[ ] app/Infrastructure/Persistence/Repositories/EloquentNotificationRepository.php

Phase 2
[ ] app/Application/Notification/Contracts/NotificationServiceInterface.php
[ ] app/Application/Notification/Contracts/NotificationHandlerInterface.php
[ ] app/Application/Notification/DTOs/NotificationDTO.php
[ ] app/Application/Notification/DTOs/CreateNotificationDTO.php
[ ] app/Infrastructure/Notifications/NotificationService.php
[ ] app/Infrastructure/Notifications/NullNotificationService.php
[ ] app/Infrastructure/Notifications/Jobs/WriteNotificationJob.php
[ ] config/notification.php

Phase 3
[ ] app/Infrastructure/Notifications/Handlers/TaskAssignedHandler.php
[ ] app/Infrastructure/Notifications/Handlers/TaskStatusChangedHandler.php
[ ] app/Infrastructure/Notifications/Handlers/TenantMemberAddedHandler.php
[ ] app/Infrastructure/Notifications/Handlers/TenantMemberRemovedHandler.php
[ ] app/Infrastructure/Notifications/Handlers/TenantRoleChangedHandler.php

Phase 4
[ ] app/Application/Task/UseCases/UpdateTaskUseCase.php     (sửa)
[ ] app/Application/Task/UseCases/AssignTaskUseCase.php     (tạo mới)
[ ] app/Application/Tenant/UseCases/AttachUserToTenantUseCase.php  (tạo mới)
[ ] app/Application/Tenant/UseCases/DetachUserFromTenantUseCase.php (tạo mới)
[ ] app/Application/Tenant/UseCases/ChangeUserRoleUseCase.php      (tạo mới)

Phase 5
[ ] resources/views/components/notification-bell.blade.php

Phase 6
[ ] app/Console/Commands/CleanupOldNotificationsCommand.php
[ ] bootstrap/app.php  (thêm scheduler)

Phase 7
[ ] tests/Unit/Notification/NullNotificationServiceTest.php
[ ] tests/Unit/Notification/TaskAssignedHandlerTest.php
[ ] tests/Unit/Notification/TenantMemberRemovedHandlerTest.php
[ ] tests/Feature/Notification/WriteNotificationJobTest.php
[ ] tests/Feature/Notification/CleanupCommandTest.php
```

---

## Timeline ước tính

| Phase | Thời gian |
|---|---|
| 1 — Foundation | 1 buổi |
| 2 — Service Layer | 1 buổi |
| 3 — Handlers (5 cái) | 1 buổi |
| 4 — Integration UseCase | 1 buổi |
| 5 — Livewire UI | 1 buổi |
| 6 — Cleanup | 0.5 buổi |
| 7 — Tests | 1 buổi |
| **Tổng** | **~6.5 buổi** |
