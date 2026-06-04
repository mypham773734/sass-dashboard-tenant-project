<?php

namespace App\Domain\Task\Repositories;

use App\Domain\Task\Entities\TaskEntity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    public function findAllByTenantId(int $tenantId, int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id, int $tenantId): ?TaskEntity;

    public function create(TaskEntity $entity): TaskEntity;

    public function update(TaskEntity $entity): TaskEntity;

    public function delete(int $id, int $tenantId): bool;
}
