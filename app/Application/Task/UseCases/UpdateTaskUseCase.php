<?php

namespace App\Application\Task\UseCases;

use App\Application\Task\DTOs\UpdateTaskDTO;
use App\Domain\Task\Entities\TaskEntity;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

class UpdateTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function execute(int $id, int $tenantId, UpdateTaskDTO $dto): TaskEntity
    {
        $existing = $this->taskRepository->findById($id, $tenantId);

        if (! $existing) {
            throw new \DomainException('Task not found.');
        }

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

        return $this->taskRepository->update($updated);
    }
}
