<?php

namespace App\Application\Tenant\UseCases;
use App\Models\Role; 
use App\Models\Permission;
use App\Domain\Tenant\Entities\TenantEntity; 

class SetupDefaultTenantRolesAndPermissionsUseCase
{
    public function __construct(){}
    public function execute(TenantEntity $tenant)
    {
        $permissionModels = [];
        $permissionMatrix = config('rolepermissiondefault');
        $allPermissions = array_unique(array_merge(...array_values($permissionMatrix)));

        foreach ($allPermissions as $permissionName) {
            $permissionModels[$permissionName] = Permission::firstOrCreate([
                'name' => $permissionName,
                'tenant_id' => $tenant->id,
                'guard_name' => 'web',
            ]);
        }

        foreach ($permissionMatrix as $roleName => $permissionNames) {
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
        
        return true; 
    }
}
