<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    private array $permissionMatrix = [
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
        $allPermissions = array_unique(array_merge(...array_values($this->permissionMatrix)));

        foreach (Tenant::all() as $tenant) {
            $permissionModels = [];

            foreach ($allPermissions as $permissionName) {
                $permissionModels[$permissionName] = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'tenant_id' => $tenant->id,
                    'guard_name' => 'web',
                ]);
            }

            foreach ($this->permissionMatrix as $roleName => $permissionNames) {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'tenant_id' => $tenant->id,
                    'guard_name' => 'web',
                ]);

                $permissions = collect($permissionNames)
                    ->map(fn($name) => $permissionModels[$name])
                    ->all();

                $role->syncPermissions($permissions);
            }

            $this->command->info("Created roles & permissions for tenant: {$tenant->name}");
        }
    }
}
