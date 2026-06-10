# Notification System — Implementation Plan

**Status:** Planning  
**Level:** Developer ready to code  
**Purpose:** Step-by-step guide to build the system

---

## 📋 Overview: 7 Phases

| Phase | What | Effort | Time |
|---|---|---|---|
| 1 | Database + Domain layer | Foundation | 1 session |
| 2 | Service + Interface + Job + Config | Core | 1 session |
| 3 | Handlers (1 generic + 3 base classes) | Logic | 0.75 session |
| 4 | Inject into 5 UseCases | Integration | 1 session |
| 5 | Livewire bell component | UI | 1 session |
| 6 | Cleanup command | Ops | 0.5 session |
| 7 | Tests | QA | 1 session |
| **Total** | | | **6.25 sessions** |

---

## Phase 1: Database + Domain Layer

### 1.1 Create Migration

```bash
# File: database/migrations/xxxx_create_notifications_table.php
php artisan make:migration create_notifications_table
```

```php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('event');                    // 'task.assigned'
    $table->string('title');                    // 'Alice assigned you "Fix bug"'
    $table->text('body')->nullable();           // optional detail
    $table->string('url')->nullable();          // '/tasks/123'
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    $table->json('data')->nullable();           // {'task_id': 123}
    $table->timestamps();

    // Indexes
    $table->index(['user_id', 'tenant_id', 'is_read']);      // for unread count
    $table->index(['user_id', 'tenant_id', 'created_at']);   // for listing
    $table->index(['tenant_id']);                             // for cleanup
});
```

Run: `php artisan migrate`

---

### 1.2 Create Domain Entity

```php
// app/Domain/Notification/Entities/NotificationEntity.php

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

---

### 1.3 Create Repository Interface

```php
// app/Domain/Notification/Repositories/NotificationRepositoryInterface.php

interface NotificationRepositoryInterface {
    public function createForUser(
        CreateNotificationDTO $dto,
        int $userId,
        int $tenantId
    ): NotificationEntity;

    public function getUnreadByUser(int $userId, int $tenantId, int $limit = 10): array;
    public function countUnreadByUser(int $userId, int $tenantId): int;
    
    public function markAsRead(int $notificationId, int $userId): void;
    public function markAllAsRead(int $userId, int $tenantId): void;
    
    public function deleteOlderThan(int $tenantId, Carbon $before): int;
}
```

---

### 1.4 Create Eloquent Model

```php
// app/Models/Notification.php

class Notification extends Model {
    protected $fillable = [
        'tenant_id', 'user_id', 'event', 'title', 'body', 
        'url', 'is_read', 'read_at', 'data'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
```

---

### 1.5 Create Repository Implementation

```php
// app/Infrastructure/Persistence/Repositories/EloquentNotificationRepository.php

class EloquentNotificationRepository implements NotificationRepositoryInterface {
    
    public function createForUser(
        CreateNotificationDTO $dto,
        int $userId,
        int $tenantId
    ): NotificationEntity {
        $model = Notification::create([
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
            'event'     => $dto->event,
            'title'     => $dto->title,
            'body'      => $dto->body,
            'url'       => $dto->url,
            'is_read'   => false,
            'data'      => $dto->data,
        ]);

        return $this->toEntity($model);
    }

    public function getUnreadByUser(int $userId, int $tenantId, int $limit = 10): array {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->toArray();
    }

    public function countUnreadByUser(int $userId, int $tenantId): int {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead(int $notificationId, int $userId): void {
        Notification::where('id', $notificationId)
            ->where('user_id', $userId)  // security: only own notifications
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function markAllAsRead(int $userId, int $tenantId): void {
        Notification::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function deleteOlderThan(int $tenantId, Carbon $before): int {
        return Notification::where('tenant_id', $tenantId)
            ->where('created_at', '<', $before)
            ->delete();
    }

    private function toEntity(Notification $model): NotificationEntity {
        return new NotificationEntity(
            id:        $model->id,
            userId:    $model->user_id,
            tenantId:  $model->tenant_id,
            event:     $model->event,
            title:     $model->title,
            body:      $model->body,
            url:       $model->url,
            isRead:    $model->is_read,
            readAt:    $model->read_at?->toDateTimeString(),
            data:      $model->data ?? [],
            createdAt: $model->created_at->toDateTimeString(),
        );
    }
}
```

---

## Phase 2: Service Layer

### 2.1 Create Interface

```php
// app/Application/Notification/Contracts/NotificationServiceInterface.php

interface NotificationServiceInterface {
    public function notifyOne(
        string $event,
        int $tenantId,
        int $userId,
        array $context = []
    ): void;

    public function notify(
        string $event,
        int $tenantId,
        array $recipientIds,
        array $context = []
    ): void;
}
```

---

### 2.2 Create Implementation

```php
// app/Infrastructure/Notifications/NotificationService.php

class NotificationService implements NotificationServiceInterface {
    
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
    ) {}

    public function notifyOne(
        string $event,
        int $tenantId,
        int $userId,
        array $context = []
    ): void {
        if (!config('notification.enabled', true)) return;
        if (!$this->isEventEnabled($event)) return;

        $context['__event__'] = $event;  // include event in context

        WriteNotificationJob::dispatch($event, $tenantId, $userId, $context)
            ->onQueue(config('notification.queue', 'notifications'));
    }

    public function notify(
        string $event,
        int $tenantId,
        array $recipientIds,
        array $context = []
    ): void {
        foreach ($recipientIds as $userId) {
            $this->notifyOne($event, $tenantId, $userId, $context);
        }
    }

    private function isEventEnabled(string $event): bool {
        return config("notification.event_types.{$event}.enabled", true);
    }
}
```

---

### 2.3 Create NullService (for tests)

```php
// app/Infrastructure/Notifications/NullNotificationService.php

class NullNotificationService implements NotificationServiceInterface {
    
    private array $sent = [];

    public function notifyOne(
        string $event,
        int $tenantId,
        int $userId,
        array $context = []
    ): void {
        $this->sent[] = compact('event', 'tenantId', 'userId', 'context');
    }

    public function notify(
        string $event,
        int $tenantId,
        array $recipientIds,
        array $context = []
    ): void {
        foreach ($recipientIds as $userId) {
            $this->notifyOne($event, $tenantId, $userId, $context);
        }
    }

    public function assertNotified(string $event, ?int $userId = null): bool {
        return collect($this->sent)->contains(function ($item) use ($event, $userId) {
            if ($item['event'] !== $event) return false;
            if ($userId !== null && $item['userId'] !== $userId) return false;
            return true;
        });
    }

    public function getSent(): array {
        return $this->sent;
    }

    public function reset(): void {
        $this->sent = [];
    }
}
```

---

### 2.4 Create DTO

```php
// app/Application/Notification/DTOs/CreateNotificationDTO.php

class CreateNotificationDTO {
    public function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $url,
        public readonly array $data = [],
    ) {}
}
```

---

### 2.5 Create Handler Interface

```php
// app/Application/Notification/Contracts/NotificationHandlerInterface.php

interface NotificationHandlerInterface {
    public function handle(int $tenantId, array $context): NotificationDTO;
}

// app/Application/Notification/DTOs/NotificationDTO.php
class NotificationDTO {
    public function __construct(
        public readonly string $event,
        public readonly array $recipientIds,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $url,
        public readonly array $data = [],
    ) {}
}
```

---

### 2.6 Create Queue Job

```php
// app/Infrastructure/Notifications/Jobs/WriteNotificationJob.php

class WriteNotificationJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly string $event,
        private readonly int    $tenantId,
        private readonly int    $userId,
        private readonly array  $context,
    ) {
        $this->onQueue(config('notification.queue', 'notifications'));
    }

    public function handle(NotificationRepositoryInterface $repo): void {
        $handler = $this->resolveHandler();
        $dto     = $handler->handle($this->tenantId, $this->context);

        $repo->createForUser(
            new CreateNotificationDTO(
                event: $dto->event,
                title: $dto->title,
                body:  $dto->body,
                url:   $dto->url,
                data:  $dto->data,
            ),
            userId:   $this->userId,
            tenantId: $this->tenantId,
        );
    }

    private function resolveHandler(): NotificationHandlerInterface {
        $handlerClass = config("notification.event_types.{$this->event}.handler")
            ?? throw new \InvalidArgumentException("Handler not configured for: {$this->event}");

        return app($handlerClass);
    }
}
```

---

### 2.7 Create Config

```php
// config/notification.php

return [
    'enabled' => env('NOTIFICATION_ENABLED', true),
    'queue'   => env('NOTIFICATION_QUEUE', 'notifications'),

    'event_types' => [
        // ========== SIMPLE: GenericHandler + config ==========
        'task.assigned' => [
            'enabled'           => env('NOTIFICATION_TASK_ASSIGNED', true),
            'handler'           => GenericNotificationHandler::class,
            'recipients'        => 'assignee_id',
            'title_template'    => '{actor_name} assigned you "{task_title}"',
            'url_template'      => 'task.show:{task_id}',
        ],

        'task.status_changed' => [
            'enabled'           => env('NOTIFICATION_TASK_STATUS_CHANGED', true),
            'handler'           => GenericNotificationHandler::class,
            'recipients'        => ['creator_id', 'assignee_id'],
            'title_template'    => 'Task status: {old_status} → {new_status}',
            'url_template'      => 'task.show:{task_id}',
        ],

        // ========== COMPLEX: BaseHandler subclasses ==========
        'tenant.member_added' => [
            'enabled'  => env('NOTIFICATION_TENANT_MEMBER_ADDED', true),
            'handler'  => TenantMemberAddedHandler::class,
        ],

        'tenant.member_removed' => [
            'enabled'  => env('NOTIFICATION_TENANT_MEMBER_REMOVED', true),
            'handler'  => TenantMemberRemovedHandler::class,
        ],

        'tenant.role_changed' => [
            'enabled'  => env('NOTIFICATION_TENANT_ROLE_CHANGED', true),
            'handler'  => TenantRoleChangedHandler::class,
        ],
    ],
];
```

---

### 2.8 Register in AppServiceProvider

```php
// app/Providers/AppServiceProvider.php

public function boot(): void {
    // ... existing bindings ...
    
    $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
    $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
}
```

---

## Phase 3: Handlers

### 3.1 GenericNotificationHandler

```php
// app/Infrastructure/Notifications/Handlers/GenericNotificationHandler.php

class GenericNotificationHandler implements NotificationHandlerInterface {
    
    public function handle(int $tenantId, array $context): NotificationDTO {
        $event  = $context['__event__'];
        $config = config("notification.event_types.{$event}");

        $title = $this->renderTemplate($config['title_template'], $context);
        $recipientIds = $this->resolveRecipients($config['recipients'], $context);
        $url = isset($config['url_template']) 
            ? $this->buildRoute($config['url_template'], $context)
            : '';

        return new NotificationDTO(
            event:        $event,
            recipientIds: $recipientIds,
            title:        $title,
            body:         null,
            url:          $url,
            data:         $context,
        );
    }

    private function renderTemplate(string $template, array $context): string {
        return preg_replace_callback('/{(\w+)}/', 
            fn($m) => $context[$m[1]] ?? $m[0], 
            $template
        );
    }

    private function resolveRecipients($config, array $context): array {
        if (is_string($config)) {
            return [$context[$config]];
        }
        if (is_array($config)) {
            return array_values(array_filter(
                array_map(fn($k) => $context[$k] ?? null, $config)
            ));
        }
        return [];
    }

    private function buildRoute(string $template, array $context): string {
        if (!preg_match('/^(\w+\.\w+):(.+)$/', $template, $m)) return '';
        $routeName = $m[1];
        $paramKey  = $m[2];
        $paramValue = $context[$paramKey] ?? null;
        return $paramValue ? route($routeName, $paramValue) : '';
    }
}
```

---

### 3.2 BaseNotificationHandler

```php
// app/Infrastructure/Notifications/Handlers/BaseNotificationHandler.php

abstract class BaseNotificationHandler implements NotificationHandlerInterface {
    
    protected string $event;
    protected array $requiredContext = [];

    final public function handle(int $tenantId, array $context): NotificationDTO {
        $this->assertContextComplete($context);
        
        return new NotificationDTO(
            event:        $this->event,
            recipientIds: $this->resolveRecipients($tenantId, $context),
            title:        $this->renderTitle($context),
            body:         $this->renderBody($tenantId, $context),
            url:          $this->buildUrl($tenantId, $context),
            data:         $context,
        );
    }

    abstract protected function resolveRecipients(int $tenantId, array $context): array;
    abstract protected function renderTitle(array $context): string;

    protected function renderBody(int $tenantId, array $context): ?string { return null; }
    protected function buildUrl(int $tenantId, array $context): string { return ''; }

    private function assertContextComplete(array $context): void {
        $missing = array_diff($this->requiredContext, array_keys($context));
        if ($missing) {
            throw new \InvalidArgumentException(
                "Missing context for {$this->event}: " . implode(', ', $missing)
            );
        }
    }
}
```

---

### 3.3 TenantMemberAddedHandler

```php
// app/Infrastructure/Notifications/Handlers/TenantMemberAddedHandler.php

class TenantMemberAddedHandler extends BaseNotificationHandler {
    
    protected string $event = 'tenant.member_added';
    protected array $requiredContext = ['new_user_name', 'new_user_id'];

    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    protected function resolveRecipients(int $tenantId, array $context): array {
        return $this->userRepo->findAdminsByTenant($tenantId);
    }

    protected function renderTitle(array $context): string {
        return "{$context['new_user_name']} joined the workspace";
    }

    protected function buildUrl(int $tenantId, array $context): string {
        return route('tenant.members');
    }
}
```

---

### 3.4 & 3.5 TenantMemberRemovedHandler + TenantRoleChangedHandler

Similar structure (see architecture doc for examples).

---

## Phase 4: Inject into UseCases

```php
// app/Application/Task/UseCases/UpdateTaskUseCase.php

class UpdateTaskUseCase {
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly NotificationServiceInterface $notificationService,  // ADD
    ) {}

    public function execute(int $id, UpdateTaskDTO $dto, int $tenantId): TaskEntity {
        $old  = $this->repository->findById($id);
        $task = $this->repository->update($id, $dto);

        // Notify if status changed
        if ($old->status !== $task->status && $task->assigneeId) {
            $this->notificationService->notifyOne('task.status_changed', $tenantId, $task->assigneeId, [
                'task_id'    => $task->id,
                'task_title' => $task->title,
                'old_status' => $old->status,
                'new_status' => $task->status,
            ]);
        }

        return $task;
    }
}
```

Repeat for: AttachUserToTenantUseCase, DetachUserFromTenantUseCase, ChangeUserRoleUseCase

---

## Phase 5: Livewire UI

```php
// resources/views/components/notification-bell.blade.php (Livewire Volt)

<div wire:poll.5s>
    <!-- Badge -->
    @if($unreadCount > 0)
        <button @click="$toggle('isOpen')" class="relative">
            🔔
            <span class="badge">{{ $unreadCount }}</span>
        </button>
    @else
        <button @click="$toggle('isOpen')">🔔</button>
    @endif

    <!-- Dropdown -->
    @if($isOpen)
        <div class="dropdown">
            <div class="header">
                Notifications
                <button wire:click="markAllAsRead">Mark All ✓</button>
            </div>

            @forelse($notifications as $notif)
                <div class="item" wire:click="markRead({{ $notif->id }})">
                    <span class="dot">{{ $notif->is_read ? '○' : '●' }}</span>
                    <a href="{{ $notif->url }}">{{ $notif->title }}</a>
                </div>
            @empty
                <p>No notifications</p>
            @endforelse
        </div>
    @endif
</div>

@php
public function mount() {
    $this->refresh();
}

public function refresh() {
    $userId = auth()->id();
    $tenantId = tenantContext()->getId();
    
    $this->unreadCount = app(NotificationRepositoryInterface::class)
        ->countUnreadByUser($userId, $tenantId);
    
    $this->notifications = app(NotificationRepositoryInterface::class)
        ->getUnreadByUser($userId, $tenantId, 10);
}

public function markRead($notificationId) {
    app(NotificationRepositoryInterface::class)->markAsRead(
        $notificationId,
        auth()->id()
    );
    $this->refresh();
}

public function markAllAsRead() {
    app(NotificationRepositoryInterface::class)->markAllAsRead(
        auth()->id(),
        tenantContext()->getId()
    );
    $this->refresh();
}
@endphp
```

---

## Phase 6: Cleanup Command

```php
// app/Console/Commands/CleanupOldNotificationsCommand.php

class CleanupOldNotificationsCommand extends Command {
    protected $signature = 'notification:cleanup {--days=30}';

    public function handle(NotificationRepositoryInterface $repo) {
        $days  = (int)$this->option('days');
        $before = now()->subDays($days);

        $deleted = $repo->deleteOlderThan(tenantContext()->getId(), $before);
        
        $this->info("Deleted {$deleted} old notifications");
    }
}

// Register in bootstrap/app.php
Schedule::command(CleanupOldNotificationsCommand::class)->dailyAt('03:00');
```

---

## Phase 7: Tests

```php
// tests/Feature/Notification/UpdateTaskNotifyTest.php

class UpdateTaskNotifyTest extends TestCase {
    
    public function test_notify_on_status_change() {
        // Bind null service
        $this->app->bind(NotificationServiceInterface::class, NullNotificationService::class);
        
        $task = Task::factory()->create(['status' => 'todo']);
        
        $this->useCase->execute($task->id, new UpdateTaskDTO(status: 'done'), $task->tenant_id);
        
        // Assert
        $null = app(NullNotificationService::class);
        $this->assertTrue($null->assertNotified('task.status_changed', $task->assignee_id));
    }
}
```

---

## ✅ Files Created Summary

| Phase | Files | Count |
|---|---|---|
| 1 | Migration + Entity + Interface + Model + Repository | 5 |
| 2 | Interface + Service + NullService + DTO + Handler Interface + Job + Config | 7 |
| 3 | GenericHandler + BaseHandler + 3 complex handlers | 5 |
| 4 | Update 5 UseCases | - (update only) |
| 5 | Livewire component | 1 |
| 6 | Cleanup command | 1 |
| 7 | Test files | ~5 |
| **Total** | | **~24 files** |

---

## 🚀 Next: Read Architecture Doc

Confused? Read [02-architecture.md](./02-architecture.md) for detailed explanations.
