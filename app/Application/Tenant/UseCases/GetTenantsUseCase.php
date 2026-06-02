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

    /**
     * @return \App\Domain\Tenant\Entities\TenantEntity[]
     */
    public function execute(int $userId): array
    {
        return $this->tenantRepository->findAllByUserId($userId);
    }
}
