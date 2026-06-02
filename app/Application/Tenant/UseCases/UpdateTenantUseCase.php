<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Tenant\DTOs\UpdateTenantDTO;
use App\Domain\Tenant\Entities\TenantEntity;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;

class UpdateTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function execute(string $slug, UpdateTenantDTO $dto): TenantEntity
    {
        $existing = $this->tenantRepository->findBySlug($slug);

        if ($existing === null) {
            throw new \DomainException("Tenant with slug [{$slug}] not found.");
        }

        // Build an updated entity — id and slug stay the same.
        $updated = new TenantEntity(
            id:          $existing->id,
            name:        $dto->name,
            slug:        $existing->slug,
            isActive:    $dto->isActive,
            trialEndsAt: $dto->trialEndsAt,
            settings:    $dto->settings ?? $existing->settings,
        );

        return $this->tenantRepository->update($updated);
    }
}
