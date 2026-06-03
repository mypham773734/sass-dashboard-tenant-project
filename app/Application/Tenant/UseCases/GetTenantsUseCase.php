<?php

namespace App\Application\Tenant\UseCases;

use App\Domain\Tenant\Repositories\TenantRepositoryInterface;

/**
 * Returns all tenants that belong to the authenticated user.
 * Tenant isolation is enforced here — we always scope by userId.
 */
class GetTenantsUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function execute(int $userId, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->tenantRepository->findAllByUserId($userId, $perPage);
    }
}
