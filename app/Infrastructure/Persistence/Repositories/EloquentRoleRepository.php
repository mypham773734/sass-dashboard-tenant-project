<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Role\Entities\RoleEntity;
use App\Domain\Role\Repositories\RoleRepositoryInterface;
use App\Models\Role;
use App\Domain\Tenant\Entities\TenantEntity;
use App\Models\User; 

class EloquentRoleRepository implements RoleRepositoryInterface
{
    public function findByNameAndTenant(string $name, int $tenantId): ?RoleEntity
    {
        $role = Role::where('name', $name)
            ->where('tenant_id', $tenantId)
            ->where('guard_name', 'web')
            ->first();
        return $role ? $this->toEntity($role) : null;
    }

    public function getRolesByTenant(int $tenantId): array
    {
        $roles = Role::where('tenant_id', $tenantId)
            ->where('guard_name', 'web')
            ->get()
            ->map(fn (Role $role) => $this->toEntity($role))
            ->all();

        return $roles; 
    }

    public function getUserRoleForTenant(int $userId, int $tenantId): ?RoleEntity{
        $user = User::findOrFail($userId)
                ->rolesForTenant($tenantId)
                ->first(); 
        return $user ? $this->toEntity($user->roles) : null;
    }

    /** Gỡ mọi role scope-theo-tenant hiện có của user, rồi gán $roleName. */
    public function assignUserRole(int $userId, int $tenantId, string $roleName):void {
        $user = User::findOrFail($userId)->first(); 

        $role = Role::where('tenant_id', $tenantId)
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->firstOrFail();
        
        $user->rolesForTenant($tenantId)->each(fn(Role $oldRole) => $user->removeRole($oldRole)); 

        $user->assignRole($roleName); 
    }       

    /**
     * Tìm các user có tenantId và role Name
     * */ 
    public function findUserIdsByTenantAndRoles(int $tenantId, array $roleNames): array {

        $usersId = User::whereHas('roles', function ($q) use ($tenantId, $roleNames){
            $q->where('tenant_id', $tenantId)->where('name', $roleNames); 
        })->pluck('id')->all(); 

        return $usersId; 
    }

    private function toEntity(Role $model)
    {
        return new RoleEntity(
            id: $model->id,
            name: $model->name,
            guardName: $model->guard_name,
            tenantId: $model->tenant_id,
        );
    }
}




