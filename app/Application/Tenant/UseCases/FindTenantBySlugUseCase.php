<?php

namespace App\Application\Tenant\UseCases;

use App\Domain\Tenant\Entities\TenantEntity;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;

class FindTenantBySlugUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function execute(string $slug, int $userId): ?TenantEntity
    {
        $entity = $this->tenantRepository->findBySlug($slug);

        if (! $entity || ! $this->tenantRepository->hasUser($entity->id, $userId)) {
            return null;
        }

        return $entity;
    }
}
