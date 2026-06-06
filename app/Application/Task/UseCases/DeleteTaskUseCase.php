<?php

namespace App\Application\Task\UseCases;

use App\Application\Audit\AuditLoggerInterface;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

class DeleteTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly AuditLoggerInterface    $audit,
    ) {}

    public function execute(int $id, int $tenantId): void
    {
        $task = $this->taskRepository->findById($id, $tenantId);

        if (! $task) {
            throw new \DomainException('Task not found.');
        }

        $snapshot = [
            'title'    => $task->title,
            'status'   => $task->status,
            'priority' => $task->priority,
        ];

        $this->taskRepository->delete($id, $tenantId);

        $this->audit->log(
            action:     'task.deleted',
            entityId:   $id,
            entityType: 'Task',
            oldValues:  $snapshot,
        );
    }
}
