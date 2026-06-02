<?php

namespace App\Application\Tenant\UseCases;

use App\Domain\Tenant\Repositories\TenantRepositoryInterface;

/**
 * Deletes a tenant after verifying two business rules:
 * 1. The requesting user must belong to the tenant.
 * 2. You cannot delete the tenant you are currently using.
 */
class DeleteTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function execute(string $slug, int $userId, ?int $currentTenantId): bool
    {
        $tenant = $this->tenantRepository->findBySlug($slug);

        if ($tenant === null) {
            throw new \DomainException("Tenant with slug [{$slug}] not found.");
        }

        // Rule 1: requesting user must be a member of this tenant.
        if (! $this->tenantRepository->hasUser($tenant->id, $userId)) {
            throw new \DomainException('You do not have permission to delete this tenant.');
        }

        // Rule 2: cannot delete the currently active tenant.
        if ($currentTenantId !== null && $currentTenantId === $tenant->id) {
            throw new \DomainException('Cannot delete the tenant that is currently selected.');
        }

        $this->tenantRepository->detachAllUsers($tenant->id);

        return $this->tenantRepository->forceDelete($tenant->id);
    }
}
