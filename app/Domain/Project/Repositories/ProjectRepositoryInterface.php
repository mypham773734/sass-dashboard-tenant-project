<?php

namespace App\Domain\Project\Repositories;

use App\Domain\Project\Entities\ProjectEntity;
use Illuminate\Support\Collection;

interface ProjectRepositoryInterface
{
    public function findAllByTenantId(int $tenantId, int $perPage = 10);

    public function getAllByTenantId(int $tenantId): Collection;

    public function findById(int $id, int $tenantId): ?ProjectEntity;

    public function create(ProjectEntity $entity): ProjectEntity;

    public function update(ProjectEntity $entity): ProjectEntity;

    public function delete(int $id, int $tenantId): bool;
}
