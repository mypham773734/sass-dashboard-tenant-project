# Approach B - Detailed Implementation Plan

**Duration:** 3 days  
**Target:** Production-ready RBAC with Spatie  
**Status:** Ready to implement

---

## Overview: 4 Phases

```
Phase 1: Database & Models (Day 1)
    ↓
Phase 2: Policies & Authorization (Day 1-2)
    ↓
Phase 3: Routes & Controllers (Day 2)
    ↓
Phase 4: UI & Testing (Day 2-3)
```

---

## PHASE 1: Database & Models (Day 1 — Morning)

### Step 1.1: Create Migration - Add tenant_id to Spatie tables

**File:** `database/migrations/2026_06_04_000000_add_tenant_id_to_permission_tables.php`

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

**Run:**
```bash
php artisan migrate
```

---

### Step 1.2: Extend Spatie Role Model

**File:** `app/Models/Role.php`

```php
<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Builder;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'tenant_id'];

    /**
     * Scope: Only roles for a specific tenant
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Get global roles (no tenant)
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Check if role belongs to tenant
     */
    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }
}
```

---

### Step 1.3: Extend Spatie Permission Model

**File:** `app/Models/Permission.php`

```php
<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Builder;

class Permission extends SpatiePermission
{
    protected $fillable = ['name', 'guard_name', 'tenant_id'];

    /**
     * Scope: Only permissions for a specific tenant
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Get global permissions (no tenant)
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Check if permission belongs to tenant
     */
    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }
}
```

---

### Step 1.4: Update User Model - Add tenant-aware methods

**File:** `app/Models/User.php` (ADD these methods)

```php
<?php

namespace App\Models;

// ... existing code ...

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    // ... existing code ...

    /**
     * Get all roles for a specific tenant
     */
    public function rolesForTenant(int $tenantId)
    {
        return $this->roles()
            ->where('tenant_id', $tenantId)
            ->get();
    }

    /**
     * Check if user has permission in specific tenant
     */
    public function hasPermissionInTenant(string $permission, int $tenantId): bool
    {
        return $this->rolesForTenant($tenantId)
            ->flatMap(fn($role) => $role->permissions)
            ->contains('name', $permission)
            || $this->directPermissions()
                ->where('name', $permission)
                ->where('tenant_id', $tenantId)
                ->exists();
    }

    /**
     * Check if user has role in specific tenant
     */
    public function hasRoleInTenant(string $role, int $tenantId): bool
    {
        return $this->rolesForTenant($tenantId)
            ->contains('name', $role);
    }

    /**
     * Get user's primary role in tenant (usually first role)
     */
    public function getPrimaryRoleInTenant(int $tenantId): ?Role
    {
        return $this->rolesForTenant($tenantId)->first();
    }

    /**
     * Get current tenant from session
     */
    public function getCurrentTenantId(): ?int
    {
        return session('current_tenant_id');
    }

    /**
     * Direct permissions (assigned without role) - with tenant scope
     */
    public function directPermissions()
    {
        return $this->permissions();
    }
}
```

---

### Step 1.5: Update Spatie Config

**File:** `config/permission.php` (MODIFY these lines)

```php
return [
    // ... existing config ...

    'models' => [
        'permission' => App\Models\Permission::class,
        'role' => App\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    // ← Keep rest as default
];
```

---

## PHASE 2: Roles, Permissions & Seeder (Day 1 — Afternoon)

### Step 2.1: Define Role-Permission Matrix

**File:** `database/seeders/PermissionMatrixSeeder.php` (reference - do not run)

```php
<?php

namespace Database\Seeders;

/**
 * PERMISSION MATRIX — Define which roles have which permissions
 * 
 * Permissions (25):
 * ├─ Tenant (5): view, edit, delete, invite_user, remove_user
 * ├─ Project (5): view, create, edit, delete, view_all
 * ├─ Task (10): view, create, edit, delete, view_all, assign, edit_status, edit_own, delete_own, view_own
 * ├─ Team (2): view, manage
 * └─ Dashboard (1): view
 * 
 * Roles (6):
 * 1. owner      — Created tenant, full control
 * 2. admin      — Manage team, projects, tasks
 * 3. manager    — Create/manage tasks + projects, limited team
 * 4. member     — Create tasks, view assigned tasks
 * 5. guest      — View-only
 * 6. custom     — (placeholder for future custom roles)
 */

const PERMISSION_MATRIX = [
    'owner' => [
        // Tenant
        'tenant:view', 'tenant:edit', 'tenant:delete',
        'tenant:invite_user', 'tenant:remove_user',
        // Project
        'project:view', 'project:view_all', 'project:create', 
        'project:edit', 'project:delete',
        // Task
        'task:view', 'task:view_all', 'task:view_own',
        'task:create', 'task:edit', 'task:edit_own', 'task:edit_all',
        'task:delete', 'task:delete_own', 'task:delete_all',
        'task:assign', 'task:edit_status',
        // Team
        'team:view', 'team:manage',
        // Dashboard
        'dashboard:view',
    ],
    'admin' => [
        // Tenant
        'tenant:view',
        // Project
        'project:view', 'project:view_all', 'project:create',
        'project:edit', 'project:delete',
        // Task
        'task:view', 'task:view_all', 'task:view_own',
        'task:create', 'task:edit', 'task:edit_own', 'task:edit_all',
        'task:delete', 'task:delete_all',
        'task:assign', 'task:edit_status',
        // Team
        'team:view', 'team:manage',
        // Dashboard
        'dashboard:view',
    ],
    'manager' => [
        // Tenant
        'tenant:view',
        // Project
        'project:view', 'project:view_all', 'project:create',
        'project:edit',
        // Task
        'task:view', 'task:view_all', 'task:view_own',
        'task:create', 'task:edit', 'task:edit_own', 'task:edit_all',
        'task:assign', 'task:edit_status',
        // Team
        'team:view',
        // Dashboard
        'dashboard:view',
    ],
    'member' => [
        // Tenant
        'tenant:view',
        // Project
        'project:view', 'project:view_all',
        // Task
        'task:view', 'task:view_own',
        'task:create', 'task:edit_own', 'task:edit_status',
        // Team
        'team:view',
        // Dashboard
        'dashboard:view',
    ],
    'guest' => [
        // Tenant
        'tenant:view',
        // Project
        'project:view', 'project:view_all',
        // Task
        'task:view', 'task:view_own',
        // Team
        'team:view',
        // Dashboard
        'dashboard:view',
    ],
];
```

---

### Step 2.2: Create Seeder - Roles & Permissions

**File:** `database/seeders/RolePermissionSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    private $permissionMatrix = [
        'owner' => [
            'tenant:view', 'tenant:edit', 'tenant:delete',
            'tenant:invite_user', 'tenant:remove_user',
            'project:view', 'project:view_all', 'project:create', 
            'project:edit', 'project:delete',
            'task:view', 'task:view_all', 'task:view_own',
            'task:create', 'task:edit', 'task:edit_own', 'task:edit_all',
            'task:delete', 'task:delete_own', 'task:delete_all',
            'task:assign', 'task:edit_status',
            'team:view', 'team:manage',
            'dashboard:view',
        ],
        'admin' => [
            'tenant:view',
            'project:view', 'project:view_all', 'project:create',
            'project:edit', 'project:delete',
            'task:view', 'task:view_all', 'task:view_own',
            'task:create', 'task:edit', 'task:edit_own', 'task:edit_all',
            'task:delete', 'task:delete_all',
            'task:assign', 'task:edit_status',
            'team:view', 'team:manage',
            'dashboard:view',
        ],
        'manager' => [
            'tenant:view',
            'project:view', 'project:view_all', 'project:create',
            'project:edit',
            'task:view', 'task:view_all', 'task:view_own',
            'task:create', 'task:edit', 'task:edit_own', 'task:edit_all',
            'task:assign', 'task:edit_status',
            'team:view',
            'dashboard:view',
        ],
        'member' => [
            'tenant:view',
            'project:view', 'project:view_all',
            'task:view', 'task:view_own',
            'task:create', 'task:edit_own', 'task:edit_status',
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
        // Clear existing data
        Permission::forTenant(null)->delete();
        Role::forTenant(null)->delete();

        // Get all permissions needed
        $allPermissions = array_unique(array_merge(...array_values($this->permissionMatrix)));

        // Create permissions for EACH tenant
        foreach (Tenant::all() as $tenant) {
            // Create permissions for this tenant
            $permissionModels = [];
            foreach ($allPermissions as $permissionName) {
                $permissionModels[$permissionName] = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'tenant_id' => $tenant->id,
                    'guard_name' => 'web',
                ]);
            }

            // Create roles for this tenant
            foreach ($this->permissionMatrix as $roleName => $permissionNames) {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'tenant_id' => $tenant->id,
                    'guard_name' => 'web',
                ]);

                // Assign permissions to role
                $permissions = collect($permissionNames)
                    ->map(fn($name) => $permissionModels[$name])
                    ->all();

                $role->syncPermissions($permissions);
            }

            echo "✓ Created roles & permissions for tenant: {$tenant->name}\n";
        }
    }
}
```

**Register seeder:** Add to `database/seeders/DatabaseSeeder.php`

```php
public function run(): void
{
    // ... existing code ...
    $this->call(RolePermissionSeeder::class);
}
```

**Run:**
```bash
php artisan db:seed --class=RolePermissionSeeder
```

---

## PHASE 2 (continued): Policies (Day 1 — Late Afternoon)

### Step 2.3: Create TaskPolicy

**File:** `app/Policies/TaskPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;

class TaskPolicy
{
    /**
     * View any tasks (permission check only)
     */
    public function viewAny(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('task:view', $tenantId);
    }

    /**
     * View specific task (permission + resource ownership)
     */
    public function view(User $user, Task $task): bool
    {
        // If user has task:view_all, allow
        if ($user->hasPermissionInTenant('task:view_all', $task->tenant_id)) {
            return true;
        }

        // Otherwise, check task:view_own + ownership/assignment
        return $user->hasPermissionInTenant('task:view_own', $task->tenant_id)
            && ($task->created_by === $user->id || $task->assignee_id === $user->id);
    }

    /**
     * Create task
     */
    public function create(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('task:create', $tenantId);
    }

    /**
     * Edit specific task
     */
    public function update(User $user, Task $task): bool
    {
        // If user has task:edit_all, allow
        if ($user->hasPermissionInTenant('task:edit_all', $task->tenant_id)) {
            return true;
        }

        // Otherwise check task:edit_own + ownership
        return $user->hasPermissionInTenant('task:edit_own', $task->tenant_id)
            && $task->created_by === $user->id;
    }

    /**
     * Delete specific task
     */
    public function delete(User $user, Task $task): bool
    {
        // If user has task:delete_all, allow
        if ($user->hasPermissionInTenant('task:delete_all', $task->tenant_id)) {
            return true;
        }

        // Otherwise check task:delete_own + ownership
        return $user->hasPermissionInTenant('task:delete_own', $task->tenant_id)
            && $task->created_by === $user->id;
    }

    /**
     * Edit task status
     */
    public function updateStatus(User $user, Task $task): bool
    {
        return $user->hasPermissionInTenant('task:edit_status', $task->tenant_id);
    }

    /**
     * Assign task to user
     */
    public function assign(User $user, Task $task): bool
    {
        return $user->hasPermissionInTenant('task:assign', $task->tenant_id);
    }
}
```

### Step 2.4: Create ProjectPolicy

**File:** `app/Policies/ProjectPolicy.php`

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

### Step 2.5: Create TenantPolicy

**File:** `app/Policies/TenantPolicy.php`

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

## PHASE 3: Routes & Controllers (Day 2 — Morning)

### Step 3.1: Register Policies in AuthServiceProvider

**File:** `app/Providers/AuthServiceProvider.php`

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

### Step 3.2: Protect Task Routes with Middleware

**File:** `routes/web.php` (UPDATE task routes)

```php
Route::middleware('auth')->group(function () {
    Route::prefix('admin')->group(function () {
        // ... other routes ...

        // Task routes with permission middleware
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
});
```

---

### Step 3.3: Update TaskController - Add authorize checks

**File:** `app/Http/Controllers/Admin/TaskController.php` (UPDATE methods)

```php
public function index()
{
    try {
        $tenantId = session('current_tenant_id');
        
        // Check permission
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
        
        // Check permission
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
        
        // Check permission
        $this->authorize('create', [Task::class, $tenantId]);
        
        // ... rest of code ...
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

        if (!$task) {
            abort(404);
        }

        // Check permission using Policy
        $this->authorize('update', new Task($task->toArray()));
        
        $projects = $this->getAllProjectsUseCase->execute($tenantId);
        return view('admin.pages.task.create', compact('task', 'projects'));
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

        if (!$existing) {
            abort(404);
        }

        // Check permission
        $this->authorize('update', new Task($existing->toArray()));
        
        // ... rest of code ...
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

        if (!$task) {
            abort(404);
        }

        // Check permission
        $this->authorize('delete', new Task($task->toArray()));
        
        $this->deleteTaskUseCase->execute($id, $tenantId);
        return redirect()->route('task.index')
            ->with('success', 'Task deleted successfully.');
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return back()->with('error', 'Failed to delete task.');
    }
}
```

---

## PHASE 4: UI & Testing (Day 2-3)

### Step 4.1: Update Blade Templates - Add @can directives

**File:** `resources/views/admin/pages/task/index.blade.php` (UPDATE action buttons)

```blade
<td class="px-6 py-4 text-right">
    <div class="flex justify-end gap-2">
        @can('update', $task)
        <a href="{{ route('task.edit', $task->id) }}"
           class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
            <i class="fas fa-edit"></i>
        </a>
        @endcan

        @can('delete', $task)
        <button type="button"
                @click="$dispatch('confirm-action', {action: '{{ route('task.destroy', $task->id) }}'})"
                class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
            <i class="fas fa-trash"></i>
        </button>
        @endcan
    </div>
</td>

<!-- Hide "New Task" button if no permission -->
@can('create', [App\Models\Task::class, session('current_tenant_id')])
<a href="{{ route('task.create') }}"
   class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
        <path d="M9 3v12M3 9h12" stroke="currentColor" stroke-width="2" />
    </svg>
    New Task
</a>
@endcan
```

---

### Step 4.2: Create Test File

**File:** `tests/Feature/TaskPermissionTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPermissionTest extends RefreshDatabase
{
    protected $tenant;
    protected $project;
    protected $task;
    protected $ownerUser;
    protected $memberUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::factory()->create();

        // Create project
        $this->project = Project::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create task
        $this->task = Task::factory()->create([
            'tenant_id' => $this->tenant->id,
            'project_id' => $this->project->id,
        ]);

        // Create users
        $this->ownerUser = User::factory()->create();
        $this->memberUser = User::factory()->create();

        // Assign roles
        $ownerRole = Role::where('name', 'owner')
            ->where('tenant_id', $this->tenant->id)
            ->first();
        $memberRole = Role::where('name', 'member')
            ->where('tenant_id', $this->tenant->id)
            ->first();

        $this->ownerUser->assignRole($ownerRole);
        $this->memberUser->assignRole($memberRole);
    }

    /** @test */
    public function owner_can_view_all_tasks()
    {
        $this->actingAs($this->ownerUser);
        session(['current_tenant_id' => $this->tenant->id]);

        $response = $this->get(route('task.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function member_can_view_assigned_tasks()
    {
        $this->task->update(['assignee_id' => $this->memberUser->id]);
        $this->actingAs($this->memberUser);
        session(['current_tenant_id' => $this->tenant->id]);

        $response = $this->get(route('task.show', $this->task->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function member_cannot_delete_tasks()
    {
        $this->actingAs($this->memberUser);
        session(['current_tenant_id' => $this->tenant->id]);

        $response = $this->delete(route('task.destroy', $this->task->id));
        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_create_tasks()
    {
        $this->actingAs($this->ownerUser);
        session(['current_tenant_id' => $this->tenant->id]);

        $response = $this->post(route('task.store'), [
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response->assertRedirect(route('task.index'));
        $this->assertDatabaseHas('tasks', ['title' => 'Test Task']);
    }
}
```

---

## Checklist - Implementation

### Day 1 — Morning (Database & Models)
- [ ] Create migration: add tenant_id
- [ ] Run migration
- [ ] Extend Role model
- [ ] Extend Permission model
- [ ] Update User model with tenant-aware methods
- [ ] Update Spatie config

### Day 1 — Afternoon (Seeder & Policies)
- [ ] Create RolePermissionSeeder
- [ ] Run seeder on dev database
- [ ] Create TaskPolicy
- [ ] Create ProjectPolicy
- [ ] Create TenantPolicy
- [ ] Register policies in AuthServiceProvider

### Day 2 — Morning (Routes & Controllers)
- [ ] Protect task routes with middleware
- [ ] Update TaskController with authorize checks
- [ ] Update ProjectController similarly
- [ ] Test each route (should see 403 if no permission)

### Day 2 — Afternoon (UI)
- [ ] Add @can directives to task/index.blade.php
- [ ] Add @can directives to task/create.blade.php
- [ ] Hide/show buttons based on permissions
- [ ] Test UI: refresh page, verify buttons appear/disappear

### Day 3 (Testing)
- [ ] Create TaskPermissionTest
- [ ] Run test suite
- [ ] Test cross-tenant isolation (user from tenant A cannot access tenant B)
- [ ] Test role transitions
- [ ] Manual testing: create user, assign different roles, verify access

---

## Deployment Checklist

Before pushing to production:

- [ ] All tests passing
- [ ] Run seeder on prod (CAREFULLY - use one tenant first)
- [ ] Verify existing users still have access (assign owner role initially)
- [ ] Monitor logs for 403 errors (sign of permission issues)
- [ ] Gradual rollout: enable for 1 tenant first, then expand

---

## Rollback Plan

If something goes wrong:

```bash
# Remove permission checks from routes (add comments)
# Temporarily disable authorize() calls in controllers
# Revert migration if needed:
php artisan migrate:rollback
```

---

## Questions Before Starting?

1. ✅ Roles count: 6 roles OK?
2. ✅ Permission count: 25 permissions OK?
3. ✅ Seeder: Should we auto-assign existing users to "owner" role initially?
4. ✅ Timeline: 3 days feasible?

