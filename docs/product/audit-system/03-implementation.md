# Audit System — Implementation Guide

**Approach:** D — AuditLogger Service (Hybrid)
**Duration:** 4 days

> Event taxonomy and data structure are defined in [01-REQUIREMENTS.md](./01-REQUIREMENTS.md).
> System diagrams are in [02-ARCHITECTURE.md](./02-ARCHITECTURE.md).

---

## Phase Overview

```
Phase 1 (Day 1 morning)   — DB migration + Domain layer + Repository + Queue Job
Phase 2 (Day 1 afternoon) — AuditLogger interface + implementations + bindings
Phase 3 (Day 2)           — Inject into Use Cases + AuthAuditListener
Phase 4 (Day 3)           — Audit viewer (UseCase + Controller + Blade)
Phase 5 (Day 4 morning)   — Tests
```

---

## Phase 1: Foundation

### 1.1 — Migration

`database/migrations/2026_06_06_create_audit_logs_table.php`

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('tenant_id')->nullable();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('action', 100);
    $table->string('entity_type', 100)->nullable();
    $table->unsignedBigInteger('entity_id')->nullable();
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('created_at')->useCurrent();
    // No updated_at — immutable

    $table->index(['tenant_id', 'created_at']);
    $table->index(['tenant_id', 'user_id']);
    $table->index(['tenant_id', 'action']);
    $table->index(['entity_type', 'entity_id']);
});
```

```bash
php artisan migrate
```

---

### 1.2 — Domain Entity

`app/Domain/Audit/Entities/AuditLog.php`

```php
<?php

namespace App\Domain\Audit\Entities;

class AuditLog
{
    public function __construct(
        public readonly ?int    $id,
        public readonly ?int    $tenantId,
        public readonly ?int    $userId,
        public readonly string  $action,
        public readonly ?string $entityType,
        public readonly ?int    $entityId,
        public readonly ?array  $oldValues,
        public readonly ?array  $newValues,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly ?array  $metadata,
        public readonly ?string $createdAt,
    ) {}
}
```

---

### 1.3 — Repository Interface

`app/Domain/Audit/Repositories/AuditRepositoryInterface.php`

```php
<?php

namespace App\Domain\Audit\Repositories;

use App\Domain\Audit\Entities\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditRepositoryInterface
{
    public function create(AuditLog $auditLog): void;
    public function paginateByTenant(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
```

---

### 1.4 — Eloquent Model

`app/Models/AuditLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }
}
```

---

### 1.5 — Eloquent Repository

`app/Infrastructure/Persistence/Repositories/EloquentAuditRepository.php`

```php
<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Audit\Entities\AuditLog;
use App\Domain\Audit\Repositories\AuditRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAuditRepository implements AuditRepositoryInterface
{
    public function create(AuditLog $entity): void
    {
        \App\Models\AuditLog::create($this->toArray($entity));
    }

    public function paginateByTenant(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return \App\Models\AuditLog::query()
            ->where('tenant_id', $tenantId)
            ->when($filters['user_id'] ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['action']  ?? null, fn($q, $v) => $q->where('action', $v))
            ->when($filters['from']    ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to']      ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    private function toArray(AuditLog $entity): array
    {
        return [
            'tenant_id'   => $entity->tenantId,
            'user_id'     => $entity->userId,
            'action'      => $entity->action,
            'entity_type' => $entity->entityType,
            'entity_id'   => $entity->entityId,
            'old_values'  => $entity->oldValues,
            'new_values'  => $entity->newValues,
            'ip_address'  => $entity->ipAddress,
            'user_agent'  => $entity->userAgent,
            'metadata'    => $entity->metadata,
        ];
    }

    private function toEntity(\App\Models\AuditLog $model): AuditLog
    {
        return new AuditLog(
            id:         $model->id,
            tenantId:   $model->tenant_id,
            userId:     $model->user_id,
            action:     $model->action,
            entityType: $model->entity_type,
            entityId:   $model->entity_id,
            oldValues:  $model->old_values,
            newValues:  $model->new_values,
            ipAddress:  $model->ip_address,
            userAgent:  $model->user_agent,
            metadata:   $model->metadata,
            createdAt:  $model->created_at?->toDateTimeString(),
        );
    }
}
```

---

### 1.6 — WriteAuditLogJob

`app/Infrastructure/Queue/Jobs/WriteAuditLogJob.php`

```php
<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\Audit\Entities\AuditLog;
use App\Domain\Audit\Repositories\AuditRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class WriteAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public string $queue = 'audit';

    public function __construct(private readonly array $data) {}

    public function handle(AuditRepositoryInterface $repo): void
    {
        $repo->create(new AuditLog(
            id:         null,
            tenantId:   $this->data['tenant_id'],
            userId:     $this->data['user_id'],
            action:     $this->data['action'],
            entityType: $this->data['entity_type'] ?? null,
            entityId:   $this->data['entity_id'] ?? null,
            oldValues:  $this->data['old_values'] ?? null,
            newValues:  $this->data['new_values'] ?? null,
            ipAddress:  $this->data['ip_address'] ?? null,
            userAgent:  $this->data['user_agent'] ?? null,
            metadata:   $this->data['metadata'] ?? null,
            createdAt:  null,
        ));
    }
}
```

---

## Phase 2: AuditLogger Service

### 2.1 — AuditLoggerInterface

`app/Application/Audit/AuditLoggerInterface.php`

```php
<?php

namespace App\Application\Audit;

interface AuditLoggerInterface
{
    public function log(
        string  $action,
        ?int    $entityId   = null,
        ?string $entityType = null,
        ?array  $newValues  = null,
        ?array  $oldValues  = null,
        ?array  $metadata   = null,
    ): void;
}
```

---

### 2.2 — QueuedAuditLogger (production)

`app/Infrastructure/Audit/QueuedAuditLogger.php`

```php
<?php

namespace App\Infrastructure\Audit;

use App\Application\Audit\AuditLoggerInterface;
use App\Infrastructure\Queue\Jobs\WriteAuditLogJob;

class QueuedAuditLogger implements AuditLoggerInterface
{
    public function log(
        string  $action,
        ?int    $entityId   = null,
        ?string $entityType = null,
        ?array  $newValues  = null,
        ?array  $oldValues  = null,
        ?array  $metadata   = null,
    ): void {
        if (! config('audit.enabled', true)) {
            return;
        }

        // Capture context here — before dispatch.
        // The job only receives plain data; it never accesses session or request.
        WriteAuditLogJob::dispatch([
            'tenant_id'   => session('current_tenant_id'),
            'user_id'     => auth()->id(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'metadata'    => $metadata,
        ]);
    }
}
```

---

### 2.3 — NullAuditLogger (tests)

`app/Infrastructure/Audit/NullAuditLogger.php`

```php
<?php

namespace App\Infrastructure\Audit;

use App\Application\Audit\AuditLoggerInterface;

class NullAuditLogger implements AuditLoggerInterface
{
    private array $logs = [];

    public function log(
        string  $action,
        ?int    $entityId   = null,
        ?string $entityType = null,
        ?array  $newValues  = null,
        ?array  $oldValues  = null,
        ?array  $metadata   = null,
    ): void {
        $this->logs[] = [
            'action'      => $action,
            'entity_id'   => $entityId,
            'entity_type' => $entityType,
            'new_values'  => $newValues,
            'old_values'  => $oldValues,
            'metadata'    => $metadata,
        ];
    }

    public function assertLogged(string $action): bool
    {
        return collect($this->logs)->contains('action', $action);
    }

    public function assertNotLogged(string $action): bool
    {
        return ! $this->assertLogged($action);
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}
```

---

### 2.4 — Bindings in AppServiceProvider

`app/Providers/AppServiceProvider.php` — add to `register()`:

```php
$this->app->bind(
    \App\Application\Audit\AuditLoggerInterface::class,
    \App\Infrastructure\Audit\QueuedAuditLogger::class,
);

$this->app->bind(
    \App\Domain\Audit\Repositories\AuditRepositoryInterface::class,
    \App\Infrastructure\Persistence\Repositories\EloquentAuditRepository::class,
);
```

---

### 2.5 — Config

`config/audit.php`

```php
<?php

return [
    'enabled'        => env('AUDIT_ENABLED', true),
    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
];
```

`.env`:
```
AUDIT_ENABLED=true
AUDIT_RETENTION_DAYS=90
```

Set `AUDIT_ENABLED=false` in `.env.testing` to skip audit writes during unrelated tests.

---

## Phase 3: Integration into Use Cases

### 3.1 — Pattern for CRUD Use Cases

Inject `AuditLoggerInterface` and call `$this->audit->log()` after a successful operation.

**Create:**

```php
class CreateTaskUseCase
{
    public function __construct(
        private TaskRepositoryInterface $repo,
        private AuditLoggerInterface    $audit,
    ) {}

    public function execute(CreateTaskDTO $dto, int $tenantId, int $createdBy): TaskEntity
    {
        $task = $this->repo->create($dto, $tenantId, $createdBy);

        $this->audit->log(
            action:     'task.created',
            entityId:   $task->id,
            entityType: 'Task',
            newValues:  [
                'title'      => $task->title,
                'status'     => $task->status,
                'priority'   => $task->priority,
                'project_id' => $task->projectId,
            ],
        );

        return $task;
    }
}
```

**Update — capture oldValues BEFORE the update:**

```php
public function execute(int $id, int $tenantId, UpdateTaskDTO $dto): TaskEntity
{
    $existing = $this->repo->findById($id, $tenantId);

    $oldValues = [
        'title'    => $existing->title,
        'status'   => $existing->status,
        'priority' => $existing->priority,
    ];

    $updated = $this->repo->update($id, $tenantId, $dto);

    $this->audit->log(
        action:     'task.updated',
        entityId:   $updated->id,
        entityType: 'Task',
        oldValues:  $oldValues,
        newValues:  [
            'title'    => $updated->title,
            'status'   => $updated->status,
            'priority' => $updated->priority,
        ],
    );

    return $updated;
}
```

**Delete — snapshot BEFORE delete:**

```php
public function execute(int $id, int $tenantId): void
{
    $task = $this->repo->findById($id, $tenantId);
    $snapshot = ['title' => $task->title, 'status' => $task->status];

    $this->repo->delete($id, $tenantId);

    $this->audit->log(
        action:     'task.deleted',
        entityId:   $id,
        entityType: 'Task',
        oldValues:  $snapshot,
    );
}
```

### 3.2 — All Use Cases to update

| Use Case | Action | old_values | new_values |
|---|---|---|---|
| `CreateTaskUseCase` | `task.created` | null | title, status, priority, project_id |
| `UpdateTaskUseCase` | `task.updated` | title, status, priority | title, status, priority |
| `DeleteTaskUseCase` | `task.deleted` | title, status | null |
| `CreateProjectUseCase` | `project.created` | null | name, description |
| `UpdateProjectUseCase` | `project.updated` | name | name |
| `DeleteProjectUseCase` | `project.deleted` | name | null |

---

### 3.3 — AuthAuditListener

`app/Infrastructure/Listeners/AuthAuditListener.php`

```php
<?php

namespace App\Infrastructure\Listeners;

use App\Infrastructure\Queue\Jobs\WriteAuditLogJob;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuthAuditListener
{
    public function handleLogin(Login $event): void
    {
        WriteAuditLogJob::dispatch([
            'tenant_id'   => null,
            'user_id'     => $event->user->id,
            'action'      => 'auth.login',
            'entity_type' => 'User',
            'entity_id'   => $event->user->id,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }

    public function handleFailed(Failed $event): void
    {
        WriteAuditLogJob::dispatch([
            'tenant_id'  => null,
            'user_id'    => null,
            'action'     => 'auth.login_failed',
            'ip_address' => request()->ip(),
            'metadata'   => ['email' => $event->credentials['email'] ?? null],
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        WriteAuditLogJob::dispatch([
            'tenant_id'  => null,
            'user_id'    => $event->user?->id,
            'action'     => 'auth.logout',
            'ip_address' => request()->ip(),
        ]);
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \Illuminate\Auth\Events\Login::class  => [\App\Infrastructure\Listeners\AuthAuditListener::class . '@handleLogin'],
    \Illuminate\Auth\Events\Failed::class => [\App\Infrastructure\Listeners\AuthAuditListener::class . '@handleFailed'],
    \Illuminate\Auth\Events\Logout::class => [\App\Infrastructure\Listeners\AuthAuditListener::class . '@handleLogout'],
];
```

---

## Phase 4: Audit Viewer

### 4.1 — GetAuditLogsUseCase

`app/Application/Audit/UseCases/GetAuditLogsUseCase.php`

```php
<?php

namespace App\Application\Audit\UseCases;

use App\Domain\Audit\Repositories\AuditRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetAuditLogsUseCase
{
    public function __construct(
        private readonly AuditRepositoryInterface $repo
    ) {}

    public function execute(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        return $this->repo->paginateByTenant($tenantId, $filters);
    }
}
```

---

### 4.2 — AuditController

`app/Http/Controllers/Admin/AuditController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Application\Audit\UseCases\GetAuditLogsUseCase;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuditController extends Controller
{
    public function __construct(
        private readonly GetAuditLogsUseCase $getAuditLogsUseCase
    ) {}

    public function index(Request $request)
    {
        try {
            $tenantId = session('current_tenant_id');
            $this->authorize('viewAuditLog', Tenant::findOrFail($tenantId));

            $filters = $request->only(['user_id', 'action', 'from', 'to']);
            $logs    = $this->getAuditLogsUseCase->execute($tenantId, $filters);

            return view('admin.pages.audit.index', compact('logs'));
        } catch (AuthorizationException | HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return back()->with('error', 'Failed to load audit logs.');
        }
    }
}
```

---

### 4.3 — Route

`routes/web.php`:

```php
Route::middleware(['auth', 'can:audit:view'])->group(function () {
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
});
```

---

### 4.4 — TenantPolicy — add method

`app/Policies/TenantPolicy.php`:

```php
public function viewAuditLog(User $user, Tenant $tenant): bool
{
    return $user->hasPermissionInTenant('audit:view', $tenant->id);
}
```

> **Also add `audit:view` to RolePermissionSeeder** — Owner and Admin roles need this permission.
> See [PERMISSION_RBAC/03-IMPLEMENTATION.md](../PERMISSION_RBAC/03-IMPLEMENTATION.md) — update the `$matrix` array.

---

### 4.5 — Blade view (minimal structure)

`resources/views/admin/pages/audit/index.blade.php`:

```blade
<div class="space-y-2">
    @foreach ($logs as $log)
    <div class="border rounded p-4">
        <div class="flex gap-2 items-center">
            <span class="font-medium">{{ $log->user?->name ?? 'System' }}</span>
            <span class="text-gray-500">{{ $log->action }}</span>
            <span class="text-sm text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
        </div>
        @if ($log->old_values || $log->new_values)
        <details class="mt-2 text-sm">
            <summary>Details</summary>
            <pre>{{ json_encode(['before' => $log->old_values, 'after' => $log->new_values], JSON_PRETTY_PRINT) }}</pre>
        </details>
        @endif
    </div>
    @endforeach

    {{ $logs->withQueryString()->links() }}
</div>
```

---

## Phase 5: Tests

### Test setup — swap AuditLogger

```php
// In TestCase setUp or per test
$nullLogger = new NullAuditLogger();
$this->app->instance(AuditLoggerInterface::class, $nullLogger);
```

### Test cases

`tests/Feature/AuditLogTest.php`

```php
<?php

namespace Tests\Feature;

use App\Application\Audit\AuditLoggerInterface;
use App\Infrastructure\Audit\NullAuditLogger;
use App\Models\{User, Task, Project, Tenant, Role};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private NullAuditLogger $audit;
    private Tenant $tenant;
    private Task $task;
    private User $owner;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->audit = new NullAuditLogger();
        $this->app->instance(AuditLoggerInterface::class, $this->audit);

        $this->tenant = Tenant::factory()->create();
        $project = Project::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->task = Task::factory()->create(['tenant_id' => $this->tenant->id, 'project_id' => $project->id]);

        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();

        $ownerRole = Role::where('name', 'owner')->where('tenant_id', $this->tenant->id)->first();
        $memberRole = Role::where('name', 'member')->where('tenant_id', $this->tenant->id)->first();
        $this->owner->assignRole($ownerRole);
        $this->member->assignRole($memberRole);
    }

    /** @test */
    public function creating_task_logs_task_created(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->post(route('task.store'), ['title' => 'Test', 'status' => 'todo', 'priority' => 'medium', 'project_id' => $this->task->project_id]);

        $this->assertTrue($this->audit->assertLogged('task.created'));
    }

    /** @test */
    public function updating_task_records_old_and_new_values(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->put(route('task.update', $this->task->id), ['title' => 'Updated', 'status' => 'done']);

        $log = collect($this->audit->getLogs())->firstWhere('action', 'task.updated');
        $this->assertNotNull($log['old_values']);
        $this->assertNotNull($log['new_values']);
    }

    /** @test */
    public function deleting_task_logs_task_deleted_with_snapshot(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->delete(route('task.destroy', $this->task->id));

        $log = collect($this->audit->getLogs())->firstWhere('action', 'task.deleted');
        $this->assertNotNull($log['old_values']);
        $this->assertNull($log['new_values']);
    }

    /** @test */
    public function audit_viewer_accessible_by_owner(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get(route('audit.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function audit_viewer_blocked_for_member(): void
    {
        $this->actingAs($this->member)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get(route('audit.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function cross_tenant_audit_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();

        $this->actingAs($this->owner)
            ->withSession(['current_tenant_id' => $otherTenant->id])
            ->get(route('audit.index'))
            ->assertStatus(403);
    }
}
```

---

## Implementation Checklist

### Phase 1 — Foundation
- [ ] Migration `create_audit_logs_table` + `php artisan migrate`
- [ ] `app/Domain/Audit/Entities/AuditLog.php`
- [ ] `app/Domain/Audit/Repositories/AuditRepositoryInterface.php`
- [ ] `app/Models/AuditLog.php`
- [ ] `app/Infrastructure/Persistence/Repositories/EloquentAuditRepository.php`
- [ ] `app/Infrastructure/Queue/Jobs/WriteAuditLogJob.php`

### Phase 2 — AuditLogger Service
- [ ] `app/Application/Audit/AuditLoggerInterface.php`
- [ ] `app/Infrastructure/Audit/QueuedAuditLogger.php`
- [ ] `app/Infrastructure/Audit/NullAuditLogger.php`
- [ ] Bindings in `AppServiceProvider`
- [ ] `config/audit.php` + `.env` vars

### Phase 3 — Integration
- [ ] Inject `AuditLoggerInterface` into `CreateTaskUseCase` + call `audit->log()`
- [ ] Inject into `UpdateTaskUseCase` + capture `oldValues` before update
- [ ] Inject into `DeleteTaskUseCase` + capture snapshot before delete
- [ ] Inject into `CreateProjectUseCase`, `UpdateProjectUseCase`, `DeleteProjectUseCase`
- [ ] `app/Infrastructure/Listeners/AuthAuditListener.php`
- [ ] Register listeners in `EventServiceProvider`

### Phase 4 — Viewer
- [ ] `app/Application/Audit/UseCases/GetAuditLogsUseCase.php`
- [ ] `app/Http/Controllers/Admin/AuditController.php`
- [ ] `TenantPolicy::viewAuditLog()` method
- [ ] Route `/audit` in `routes/web.php`
- [ ] `resources/views/admin/pages/audit/index.blade.php`
- [ ] **Add `audit:view` permission to RolePermissionSeeder** (Owner + Admin)
- [ ] Sidebar link visible to Owner and Admin

### Phase 5 — Tests
- [ ] `tests/Feature/AuditLogTest.php` — 13 test cases
- [ ] Verify `NullAuditLogger` works correctly in test context
- [ ] Test cross-tenant isolation

---

## Deployment Notes

1. Run queue worker with `audit` queue before enabling: `php artisan queue:work --queue=audit,default`
2. Set `AUDIT_ENABLED=true` in production `.env`
3. Set `AUDIT_ENABLED=false` in `.env.testing` to avoid queue noise in non-audit tests
4. Monitor `failed_jobs` table — failed audit writes should alert but not block users
