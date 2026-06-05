<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaskPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Project $project;
    private User $owner;
    private User $member;
    private User $guest;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass TenantScope during test setup
        $this->tenant = Tenant::withoutGlobalScopes()->create([
            'name'      => 'Test Tenant',
            'slug'      => 'test-tenant',
            'is_active' => true,
        ]);

        $this->owner  = User::factory()->create();
        $this->member = User::factory()->create();
        $this->guest  = User::factory()->create();

        $this->project = Project::factory()->create([
            'tenant_id' => $this->tenant->id,
            'onwer_id'  => $this->owner->id,
        ]);

        $this->seedRolesForTenant($this->tenant);

        $this->assignRole($this->owner,  'owner',  $this->tenant);
        $this->assignRole($this->member, 'member', $this->tenant);
        $this->assignRole($this->guest,  'guest',  $this->tenant);

        $this->task = Task::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status'     => 'todo',
        ]);
    }

    #[Test]
    public function owner_can_view_task_list(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get(route('task.index'))
            ->assertOk();
    }

    #[Test]
    public function guest_cannot_access_create_task_page(): void
    {
        $this->actingAs($this->guest)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get(route('task.create'))
            ->assertForbidden();
    }

    #[Test]
    public function member_can_access_create_task_page(): void
    {
        $this->actingAs($this->member)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get(route('task.create'))
            ->assertOk();
    }

    #[Test]
    public function guest_cannot_delete_any_task(): void
    {
        $this->actingAs($this->guest)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->delete(route('task.destroy', $this->task->id))
            ->assertForbidden();
    }

    #[Test]
    public function member_cannot_edit_task_they_did_not_create(): void
    {
        $otherTask = Task::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status'     => 'todo',
        ]);

        $this->actingAs($this->member)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get(route('task.edit', $otherTask->id))
            ->assertForbidden();
    }

    #[Test]
    public function member_can_edit_task_they_created(): void
    {
        $ownTask = Task::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'project_id' => $this->project->id,
            'created_by' => $this->member->id,
            'status'     => 'todo',
        ]);

        $this->actingAs($this->member)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get(route('task.edit', $ownTask->id))
            ->assertOk();
    }

    #[Test]
    public function owner_can_delete_any_task(): void
    {
        $this->actingAs($this->owner)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->delete(route('task.destroy', $this->task->id))
            ->assertRedirect(route('task.index'));
    }

    #[Test]
    public function cross_tenant_task_returns_404(): void
    {
        $otherTenant = Tenant::withoutGlobalScopes()->create([
            'name'      => 'Other Tenant',
            'slug'      => 'other-tenant',
            'is_active' => true,
        ]);

        $taskFromOtherTenant = Task::factory()->create([
            'tenant_id'  => $otherTenant->id,
            'project_id' => $this->project->id,
            'created_by' => $this->owner->id,
            'status'     => 'todo',
        ]);

        $this->actingAs($this->owner)
            ->withSession(['current_tenant_id' => $this->tenant->id])
            ->get(route('task.edit', $taskFromOtherTenant->id))
            ->assertNotFound();
    }

    private function seedRolesForTenant(Tenant $tenant): void
    {
        $permissionMatrix = [
            'owner'  => [
                'task:view', 'task:view_all', 'task:view_own',
                'task:create', 'task:edit', 'task:edit_own', 'task:edit_all',
                'task:delete', 'task:delete_own', 'task:delete_all',
                'task:assign', 'task:edit_status', 'dashboard:view',
            ],
            'member' => [
                'task:view', 'task:view_own',
                'task:create', 'task:edit_own', 'task:edit_status',
                'dashboard:view',
            ],
            'guest'  => [
                'task:view', 'task:view_own', 'dashboard:view',
            ],
        ];

        $allPermissions   = array_unique(array_merge(...array_values($permissionMatrix)));
        $permissionModels = [];

        foreach ($allPermissions as $name) {
            $permissionModels[$name] = Permission::firstOrCreate([
                'name'       => $name,
                'tenant_id'  => $tenant->id,
                'guard_name' => 'web',
            ]);
        }

        foreach ($permissionMatrix as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'tenant_id'  => $tenant->id,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions(
                collect($permissions)->map(fn($p) => $permissionModels[$p])->all()
            );
        }
    }

    private function assignRole(User $user, string $roleName, Tenant $tenant): void
    {
        $role = Role::where('name', $roleName)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $user->assignRole($role);
    }
}
