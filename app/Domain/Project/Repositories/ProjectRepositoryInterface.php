<?php

namespace App\Domain\Project\Repositories;

use App\Domain\Project\Entities\ProjectEntity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function findAllByTenantId(int $tenantId, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id, int $tenantId): ?ProjectEntity;

    public function create(ProjectEntity $entity): ProjectEntity;

    public function update(ProjectEntity $entity): ProjectEntity;

    public function delete(int $id, int $tenantId): bool;
}
