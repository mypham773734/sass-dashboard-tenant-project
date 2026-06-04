<?php

namespace App\Application\Task\UseCases;

use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetTasksUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function execute(int $tenantId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->taskRepository->findAllByTenantId($tenantId, $perPage);
    }
}
