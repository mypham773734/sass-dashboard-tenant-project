<?php

namespace App\Application\Task\UseCases;

use App\Domain\Task\Entities\TaskEntity;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

class FindTaskByIdUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function execute(int $id, int $tenantId): ?TaskEntity
    {
        return $this->taskRepository->findById($id, $tenantId);
    }
}
