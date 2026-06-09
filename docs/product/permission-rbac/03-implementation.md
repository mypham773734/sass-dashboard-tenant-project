# Permission & RBAC — Implementation Guide

**Approach:** B (Extended Spatie + Tenant-Aware Models)
**Duration:** 3 days
**Status:** Ready to implement

> Permission matrix is defined in [01-REQUIREMENTS.md](./01-REQUIREMENTS.md). Do not copy it here.

---

## Phase Overview

```
Phase 1 (Day 1 morning)   — DB migration + extend models
Phase 2 (Day 1 afternoon) — Seeder + Policies
Phase 3 (Day 2 morning)   — Routes + Controllers
Phase 4 (Day 2-3)         — UI (@can) + Tests
```

---

## Phase 1: Database & Models

### 1.1 — Migration: add `tenant_id` to Spatie tables

`database/migrations/2026_06_06_add_tenant_id_to_permission_tables.php`

```php
Schema::table('roles', function (Blueprint $table) {
    $table->unsignedBigInteger('tenant_id')->nullable()->after('guard_name');
    $table->index(['tenant_id', 'name']);
});

Schema::table('permissions', function (Blueprint $table) {
    $table->unsignedBigInteger('tenant_id')->nullable()->after('guard_name');
    $table->index(['tenant_id', 'name']);
});

Schema::table('model_has_roles', function (Blueprint $table) {
    $table->unsignedBigInteger('tenant_id')->nullable()->after('model_type');
    $table->index(['model_id', 'model_type', 'tenant_id']);
});

Schema::table('model_has_permissions', function (Blueprint $table) {
    $table->unsignedBigInteger('tenant_id')->nullable()->after('model_type');
    $table->index(['model_id', 'model_type', 'tenant_id']);
});
```

```bash
php artisan migrate
```

---

### 1.2 — Extend Role model

`app/Models/Role.php`

```php
<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Builder;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'tenant_id'];

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }
}
```

---

### 1.3 — Extend Permission model

`app/Models/Permission.php`

```php
<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Builder;

class Permission extends SpatiePermission
{
    protected $fillable = ['name', 'guard_name', 'tenant_id'];

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }
}
```

---

### 1.4 — Update User model

`app/Models/User.php` — add these methods:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    public function rolesForTenant(int $tenantId)
    {
        return $this->roles()
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function hasPermissionInTenant(string $permission, int $tenantId): bool
    {
        return $this->rolesForTenant($tenantId)
            ->flatMap(fn($role) => $role->permissions)
            ->contains('name', $permission)
            || $this->permissions()
                ->where('name', $permission)
                ->where('tenant_id', $tenantId)
                ->exists();
    }

    public function hasRoleInTenant(string $role, int $tenantId): bool
    {
        return $this->rolesForTenant($tenantId)
            ->contains('name', $role);
    }

    public function getPrimaryRoleInTenant(int $tenantId): ?Role
    {
        return $this->rolesForTenant($tenantId)->first();
    }
}
```

---

### 1.5 — Update Spatie config

`config/permission.php` — point to extended models:

```php
'models' => [
    'permission' => App\Models\Permission::class,
    'role' => App\Models\Role::class,
],
```

---

## Phase 2: Seeder + Policies

### 2.1 — RolePermissionSeeder

`database/seeders/RolePermissionSeeder.php`

The permission matrix comes from [01-REQUIREMENTS.md](./01-REQUIREMENTS.md). Encoded as PHP array:

```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    private array $matrix = [
        'owner' => [
            'tenant:view', 'tenant:edit', 'tenant:delete', 'tenant:invite_user', 'tenant:remove_user',
            'project:view', 'project:view_all', 'project:create', 'project:edit', 'project:delete',
            'task:view', 'task:view_own', 'task:view_all', 'task:create',
            'task:edit', 'task:edit_own', 'task:edit_all', 'task:edit_status',
            'task:delete', 'task:delete_own', 'task:delete_all', 'task:assign',
            'team:view', 'team:manage',
            'dashboard:view',
        ],
        'admin' => [
            'tenant:view', 'tenant:invite_user', 'tenant:remove_user',
            'project:view', 'project:view_all', 'project:create', 'project:edit', 'project:delete',
            'task:view', 'task:view_own', 'task:view_all', 'task:create',
            'task:edit', 'task:edit_own', 'task:edit_all', 'task:edit_status',
            'task:delete', 'task:delete_own', 'task:delete_all', 'task:assign',
            'team:view', 'team:manage',
            'dashboard:view',
        ],
        'manager' => [
            'tenant:view',
            'project:view', 'project:view_all', 'project:create', 'project:edit',
            'task:view', 'task:view_own', 'task:view_all', 'task:create',
            'task:edit', 'task:edit_own', 'task:edit_all', 'task:edit_status',
            'task:delete_own', 'task:assign',
            'team:view',
            'dashboard:view',
        ],
        'member' => [
            'tenant:view',
            'project:view', 'project:view_all',
            'task:view', 'task:view_own', 'task:create', 'task:edit_own', 'task:edit_status',
            'team:view',
            'dashboard:view',
        ],
        'guest' => [
            'tenant:view',
            'project:view', 'project:view_all',
            'task:view', 'task:view_own',
            'team:view',
            'dashboard:view',
        ],
    ];

    public function run(): void
    {
        $allPermissionNames = array_unique(array_merge(...array_values($this->matrix)));

        foreach (Tenant::all() as $tenant) {
            // Create all permissions for this tenant
            $permissionModels = [];
            foreach ($allPermissionNames as $name) {
                $permissionModels[$name] = Permission::firstOrCreate([
                    'name' => $name,
                    'tenant_id' => $tenant->id,
                    'guard_name' => 'web',
                ]);
            }

            // Create each role and sync its permissions
            foreach ($this->matrix as $roleName => $permissionNames) {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'tenant_id' => $tenant->id,
                    'guard_name' => 'web',
                ]);

                $role->syncPermissions(
                    collect($permissionNames)->map(fn($n) => $permissionModels[$n])->all()
                );
            }
        }
    }
}
```

Register in `database/seeders/DatabaseSeeder.php`:

```php
$this->call(RolePermissionSeeder::class);
```

```bash
php artisan db:seed --class=RolePermissionSeeder
```

---

### 2.2 — TaskPolicy

`app/Policies/TaskPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;

class TaskPolicy
{
    public function viewAny(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('task:view', $tenantId);
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->hasPermissionInTenant('task:view_all', $task->tenant_id)) {
            return true;
        }

        return $user->hasPermissionInTenant('task:view_own', $task->tenant_id)
            && ($task->created_by === $user->id || $task->assignee_id === $user->id);
    }

    public function create(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('task:create', $tenantId);
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->hasPermissionInTenant('task:edit_all', $task->tenant_id)) {
            return true;
        }

        return $user->hasPermissionInTenant('task:edit_own', $task->tenant_id)
            && $task->created_by === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->hasPermissionInTenant('task:delete_all', $task->tenant_id)) {
            return true;
        }

        return $user->hasPermissionInTenant('task:delete_own', $task->tenant_id)
            && $task->created_by === $user->id;
    }

    public function updateStatus(User $user, Task $task): bool
    {
        return $user->hasPermissionInTenant('task:edit_status', $task->tenant_id);
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->hasPermissionInTenant('task:assign', $task->tenant_id);
    }
}
```

---

### 2.3 — ProjectPolicy

`app/Policies/ProjectPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;

class ProjectPolicy
{
    public function viewAny(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('project:view', $tenantId);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasPermissionInTenant('project:view', $project->tenant_id);
    }

    public function create(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('project:create', $tenantId);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermissionInTenant('project:edit', $project->tenant_id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermissionInTenant('project:delete', $project->tenant_id);
    }
}
```

---

### 2.4 — TenantPolicy

`app/Policies/TenantPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tenant;

class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:view', $tenant->id);
    }

    public function edit(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:edit', $tenant->id);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:delete', $tenant->id);
    }

    public function inviteUser(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:invite_user', $tenant->id);
    }

    public function removeUser(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:remove_user', $tenant->id);
    }
}
```

---

### 2.5 — Register Policies

`app/Providers/AuthServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\Project;
use App\Models\Tenant;
use App\Policies\TaskPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Task::class => TaskPolicy::class,
        Project::class => ProjectPolicy::class,
        Tenant::class => TenantPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
```

---

## Phase 3: Routes + Controllers

### 3.1 — Protect routes with `can:` middleware

`routes/web.php`

```php
Route::middleware('auth')->prefix('admin')->group(function () {

    Route::middleware('can:task:view')->group(function () {
        Route::get('/task', [TaskController::class, 'index'])->name('task.index');
    });

    Route::middleware('can:task:create')->group(function () {
        Route::get('/task/create', [TaskController::class, 'create'])->name('task.create');
        Route::post('/task', [TaskController::class, 'store'])->name('task.store');
    });

    Route::middleware('can:task:edit')->group(function () {
        Route::get('/task/{id}/edit', [TaskController::class, 'edit'])->name('task.edit');
        Route::put('/task/{id}', [TaskController::class, 'update'])->name('task.update');
    });

    Route::middleware('can:task:delete')->group(function () {
        Route::delete('/task/{id}', [TaskController::class, 'destroy'])->name('task.destroy');
    });
});
```

---

### 3.2 — Update TaskController

`app/Http/Controllers/Admin/TaskController.php`

```php
public function index()
{
    try {
        $tenantId = session('current_tenant_id');
        $this->authorize('viewAny', [Task::class, $tenantId]);

        $tasks = $this->getTasksUseCase->execute($tenantId);
        return view('admin.pages.task.index', compact('tasks'));
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to load tasks.');
    }
}

public function create()
{
    try {
        $tenantId = session('current_tenant_id');
        $this->authorize('create', [Task::class, $tenantId]);

        $projects = $this->getAllProjectsUseCase->execute($tenantId);
        return view('admin.pages.task.create', compact('projects'));
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to load page.');
    }
}

public function store(StoreTaskRequest $request)
{
    try {
        $tenantId = session('current_tenant_id');
        $this->authorize('create', [Task::class, $tenantId]);

        $dto = CreateTaskDTO::fromArray($request->validated());
        $this->createTaskUseCase->execute($dto, $tenantId);
        return redirect()->route('task.index')->with('success', 'Task created.');
    } catch (\DomainException $e) {
        return back()->with('error', $e->getMessage())->withInput();
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to create task.')->withInput();
    }
}

public function edit(int $id)
{
    try {
        $tenantId = session('current_tenant_id');
        $task = $this->findTaskByIdUseCase->execute($id, $tenantId);

        abort_if(!$task, 404);
        $this->authorize('update', new Task($task->toArray()));

        $projects = $this->getAllProjectsUseCase->execute($tenantId);
        return view('admin.pages.task.edit', compact('task', 'projects'));
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to load task.');
    }
}

public function update(UpdateTaskRequest $request, int $id)
{
    try {
        $tenantId = session('current_tenant_id');
        $existing = $this->findTaskByIdUseCase->execute($id, $tenantId);

        abort_if(!$existing, 404);
        $this->authorize('update', new Task($existing->toArray()));

        $dto = UpdateTaskDTO::fromArray($request->validated());
        $this->updateTaskUseCase->execute($id, $dto, $tenantId);
        return redirect()->route('task.index')->with('success', 'Task updated.');
    } catch (\DomainException $e) {
        return back()->with('error', $e->getMessage())->withInput();
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to update task.')->withInput();
    }
}

public function destroy(int $id)
{
    try {
        $tenantId = session('current_tenant_id');
        $task = $this->findTaskByIdUseCase->execute($id, $tenantId);

        abort_if(!$task, 404);
        $this->authorize('delete', new Task($task->toArray()));

        $this->deleteTaskUseCase->execute($id, $tenantId);
        return redirect()->route('task.index')->with('success', 'Task deleted.');
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to delete task.');
    }
}
```

---

## Phase 4: UI + Tests

### 4.1 — Blade @can directives

`resources/views/admin/pages/task/index.blade.php`

```blade
{{-- New Task button --}}
@can('create', [App\Models\Task::class, session('current_tenant_id')])
<a href="{{ route('task.create') }}" class="btn-primary">New Task</a>
@endcan

{{-- Action buttons per row --}}
@can('update', $task)
<a href="{{ route('task.edit', $task->id) }}" class="btn-icon-edit"></a>
@endcan

@can('delete', $task)
<button @click="confirmDelete('{{ route('task.destroy', $task->id) }}')" class="btn-icon-delete"></button>
@endcan
```

---

### 4.2 — Feature tests

`tests/Feature/TaskPermissionTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\{User, Task, Project, Tenant, Role};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Task $task;
    private User $owner;
    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $project = Project::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->task = Task::factory()->create([
            'tenant_id' => $this->tenant->id,
            'project_id' => $project->id,
        ]);

        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();

        $ownerRole = Role::where('name', 'owner')->where('tenant_id', $this->tenant->id)->first();
        $memberRole = Role::where('name', 'member')->where('tenant_id', $this->tenant->id)->first();

        $this->owner->assignRole($ownerRole);
        $this->member->assignRole($memberRole);
    }

    /** @test */
    public function owner_can_view_tasks()
    {
        $this->actingAs($this->owner);
        session(['current_tenant_id' => $this->tenant->id]);

        $this->get(route('task.index'))->assertStatus(200);
    }

    /** @test */
    public function member_cannot_delete_tasks()
    {
        $this->actingAs($this->member);
        session(['current_tenant_id' => $this->tenant->id]);

        $this->delete(route('task.destroy', $this->task->id))->assertStatus(403);
    }

    /** @test */
    public function member_cannot_edit_others_tasks()
    {
        $this->task->update(['created_by' => $this->owner->id]);

        $this->actingAs($this->member);
        session(['current_tenant_id' => $this->tenant->id]);

        $this->put(route('task.update', $this->task->id), ['title' => 'Hack'])
            ->assertStatus(403);
    }

    /** @test */
    public function cross_tenant_access_denied()
    {
        $otherTenant = Tenant::factory()->create();

        $this->actingAs($this->owner);
        session(['current_tenant_id' => $otherTenant->id]); // wrong tenant

        $this->get(route('task.index'))->assertStatus(403);
    }
}
```

---

## Implementation Checklist

### Day 1 — Morning
- [ ] Create and run migration (`tenant_id` on Spatie tables)
- [ ] Extend `Role` model
- [ ] Extend `Permission` model
- [ ] Add methods to `User` model
- [ ] Update `config/permission.php`

### Day 1 — Afternoon
- [ ] Create `RolePermissionSeeder`
- [ ] Run seeder on dev DB
- [ ] Create `TaskPolicy`
- [ ] Create `ProjectPolicy`
- [ ] Create `TenantPolicy`
- [ ] Register policies in `AuthServiceProvider`

### Day 2 — Morning
- [ ] Add `can:` middleware to task routes
- [ ] Add `$this->authorize()` to all TaskController methods
- [ ] Verify 403 returned when accessing without permission

### Day 2 — Afternoon
- [ ] Add `@can` directives to task index view
- [ ] Add `@can` to task create view
- [ ] Manual test: switch between roles, verify UI shows/hides correctly

### Day 3 — Testing
- [ ] Write `TaskPermissionTest`
- [ ] Test cross-tenant isolation
- [ ] Run full test suite

---

## Rollback

```bash
# Comment out ->middleware('can:...') in routes
# Comment out $this->authorize() in controllers
# If migration needs reverting:
php artisan migrate:rollback
```

---

## Deployment Notes

1. Run seeder on production with one tenant first, verify, then expand
2. Assign existing users `owner` role before enabling permission checks
3. Monitor logs for unexpected 403s after deploy
