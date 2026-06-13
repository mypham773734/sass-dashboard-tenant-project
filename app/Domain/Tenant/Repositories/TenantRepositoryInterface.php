<?php

namespace App\Domain\Tenant\Repositories;

use App\Domain\Tenant\Entities\TenantEntity;

/**
 * Contract for tenant persistence.
 * Domain only knows this interface — never the concrete Eloquent implementation.
 */
interface TenantRepositoryInterface
{
    /**
     * Return a paginated list of tenants that the given user belongs to.
     */
    public function findPaginatedByUserId(int $userId, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function findAllByUserId(int $userId); 

    public function findById(int $id): ?TenantEntity;

    public function findBySlug(string $slug): ?TenantEntity;

    public function create(TenantEntity $entity): TenantEntity;

    public function update(TenantEntity $entity): TenantEntity;

    /** Hard-delete a tenant (also detaches all users). */
    public function forceDelete(int $id): bool;

    // ── Relationship helpers ──────────────────────────────────────────────────

    public function attachUserWithTenant(int $tenantId, int $userId): void;

    public function detachUser(int $tenantId, int $userId): void;

    public function detachAllUsers(int $tenantId): void;

    public function hasUser(int $tenantId, int $userId): bool;

}
