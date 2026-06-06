<?php

namespace App\Application\Task\UseCases;

use App\Application\Audit\AuditLoggerInterface;
use App\Application\Task\DTOs\UpdateTaskDTO;
use App\Domain\Task\Entities\TaskEntity;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

class UpdateTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly AuditLoggerInterface    $audit,
    ) {}

    public function execute(int $id, int $tenantId, UpdateTaskDTO $dto): TaskEntity
    {
        $existing = $this->taskRepository->findById($id, $tenantId);

        if (! $existing) {
            throw new \DomainException('Task not found.');
        }

        $oldValues = [
            'title'    => $existing->title,
            'status'   => $existing->status,
            'priority' => $existing->priority,
        ];

        // Auto-set completedAt when status transitions to done
        $completedAt = $existing->completedAt;
        if ($dto->status === 'done' && $existing->status !== 'done') {
            $completedAt = new \DateTime();
        } elseif ($dto->status !== 'done') {
            $completedAt = null;
        }

        $updated = new TaskEntity(
            id:          $existing->id,
            tenantId:    $existing->tenantId,
            projectId:   $dto->projectId,
            createdBy:   $existing->createdBy,
            assigneeId:  $dto->assigneeId,
            title:       $dto->title,
            description: $dto->description,
            status:      $dto->status,
            priority:    $dto->priority,
            order:       $existing->order,
            dueDate:     $dto->dueDate ? new \DateTime($dto->dueDate) : null,
            completedAt: $completedAt,
        );

        $task = $this->taskRepository->update($updated);

        $this->audit->log(
            action:     'task.updated',
            entityId:   $task->id,
            entityType: 'Task',
            newValues:  [
                'title'    => $task->title,
                'status'   => $task->status,
                'priority' => $task->priority,
            ],
            oldValues:  $oldValues,
        );

        return $task;
    }
}
