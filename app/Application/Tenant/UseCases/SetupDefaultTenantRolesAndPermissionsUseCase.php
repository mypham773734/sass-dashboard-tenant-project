<?php

namespace App\Application\Tenant\UseCases;
use App\Models\Role; 
use App\Models\Permission;
use App\Domain\Tenant\Entities\TenantEntity;
use DomainException;
use App\Domain\Permission\Repositories\PermissionRepositoryInterface; 
use App\Domain\Permission\Entities\PermissionEntity;
use App\Domain\Role\Repositories\RoleRepositoryInterface;  
use App\Domain\Role\Entities\RoleEntity; 

class SetupDefaultTenantRolesAndPermissionsUseCase
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository, 
        private readonly RoleRepositoryInterface $roleRepository
    ){}
    public function execute(TenantEntity $tenant)
    {
        $permissionModels = [];
        $permissionMatrix = config('rolepermissiondefault');
        if($permissionMatrix == null){
            new DomainException('rolepermissiondefault is null'); 
        }
        $allPermissions = array_unique(array_merge(...array_values($permissionMatrix)));
        $tenantId = $tenant->id; 

        foreach ($allPermissions as $permissionName) {
            $permissionEntity = new PermissionEntity(
                id: null, 
                name: $permissionName,
                guardName:  'web',
                tenantId: $$tenantId
            ); 

            $permissionEntity = $this->permissionRepository->create($permissionEntity); 
        }

        foreach ($permissionMatrix as $roleName => $permissionNames) {
            $permissions = collect($permissionNames)
                ->map(fn($name) => $permissionModels[$name])
                ->all();

            $roleEntity = new RoleEntity(
                id: null, 
                name: $roleName, 
                guardName: 'web', 
                tenantId: $tenantId
            ); 

            $roleEntity = $this->roleRepository->createAndSyncPermission($roleEntity, $permissions);
        }
        
        return true; 
    }
}
