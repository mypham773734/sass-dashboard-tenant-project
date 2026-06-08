<?php

namespace App\Application\Tenant\UseCases;

use App\Domain\Tenant\Entities\TenantEntity;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use App\DTOs\Tenants\CreateTenantDTO; 

/**
 * Orchestrates tenant creation:
 * 1. Build a TenantEntity from the DTO.
 * 2. Persist it via the repository.
 * 3. Attach the creator as 'admin' — this is a business rule, not a controller concern.
 */
class CreateTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function execute(CreateTenantDTO $dto, int $creatorUserId): TenantEntity
    {
        $entity = new TenantEntity(
            id:          null,
            name:        $dto->name,
            slug:        $dto->slug,
            isActive:    $dto->is_active,
            trialEndsAt: $dto->trial_ends_at,
        );

        $created = $this->tenantRepository->create($entity);

        // Business rule: the creator is always the first admin of the tenant.
        $this->tenantRepository->attachUser($created->id, $creatorUserId, 'admin');

        return $created;
    }
}
