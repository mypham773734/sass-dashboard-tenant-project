<?php

namespace App\Application\Task\UseCases;

use App\Domain\Task\Repositories\TaskRepositoryInterface;

class DeleteTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function execute(int $id, int $tenantId): void
    {
        $task = $this->taskRepository->findById($id, $tenantId);

        if (! $task) {
            throw new \DomainException('Task not found.');
        }

        $this->taskRepository->delete($id, $tenantId);
    }
}
