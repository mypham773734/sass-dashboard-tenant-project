<?php 

namespace App\Infrastructure\Persistence\Repositories; 

use App\Domain\Permission\Repositories\PermissionRepositoryInterface; 
use App\Domain\Permission\Entities\PermissionEntity; 
use App\Models\Permission;


class EloquentPermissionRepository implements PermissionRepositoryInterface{
    public function create(PermissionEntity $permissionEntity): ?PermissionEntity{
        $permission = Permission::createOrFirst($this->toArray($permissionEntity));
        
        return $permission ? $this->toEntity($permission) : null; 
    }

    private function toArray(PermissionEntity $permissionEntity){
        return [
            'id' => $permissionEntity->id, 
            'name' => $permissionEntity->name, 
            'guard_name' => $permissionEntity->guardName, 
            'tenant_id' => $permissionEntity->tenantId, 
        ]; 
    }

    private function toEntity(Permission $model){
        return new PermissionEntity(
            id: $model->id, 
            name: $model->name, 
            guardName: $model->guard_name, 
            tenantId: $model->tenant_id, 
        ); 
    }
}