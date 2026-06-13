<?php 

namespace App\Domain\Role\Repositories;

use App\Domain\Role\Entities\RoleEntity; 
use App\Domain\Tenant\Entities\TenantEntity; 

interface RoleRepositoryInterface{
    public function findByNameAndTenant(string $name, int $tenantId): ?RoleEntity;

    /** @return RoleEntity[] */
    public function getRolesByTenant(int $tenantId): array;

    public function getUserRoleForTenant(int $userId, int $tenantId): ?RoleEntity;

    /** Gỡ mọi role scope-theo-tenant hiện có của user, rồi gán $roleName. */
    public function assignUserRole(int $userId, int $tenantId, string $roleName): void;

    /** @return int[] user id */
    public function findUserIdsByTenantAndRoles(int $tenantId, array $roleNames): array;
}