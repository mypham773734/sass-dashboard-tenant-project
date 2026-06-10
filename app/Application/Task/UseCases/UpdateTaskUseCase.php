<?php

namespace App\Application\Task\UseCases;

use App\Application\Audit\AuditLoggerInterface;
use App\Application\Notification\Contracts\NotificationServiceInterface;
use App\Application\Task\DTOs\UpdateTaskDTO;
use App\Domain\Task\Entities\TaskEntity;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Dom\Entity;

class UpdateTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly AuditLoggerInterface $audit,
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    public function execute(int $id, int $tenantId, UpdateTaskDTO $dto): TaskEntity
    {
        $existing = $this->taskRepository->findById($id, $tenantId);

        if (! $existing) {
            throw new \DomainException('Task not found.');
        }

        // Auto-set completedAt when status transitions to done
        $completedAt = $this->calculateCompletedAt($existing, $dto); 

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

        $this->notifySystem($existing, $task, $tenantId); 

        $this->writeLogs($task, $existing); 

        return $task;
    }

    private function writeLogs(TaskEntity $existing, TaskEntity $task){
        $oldValues = [
            'title'    => $existing->title,
            'status'   => $existing->status,
            'priority' => $existing->priority,
        ];

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
    }

    private function notifySystem(TaskEntity $existing, TaskEntity $task, int $tenantId)
    {
        $authorName = authContext()->getUser()->name; 
        // Notify on status change
        if ($existing->status !== $task->status && $task->assigneeId) {
            $this->notificationService->notifyOne(
                event:    'task.status_changed',
                tenantId: $tenantId,
                userId:   $task->assigneeId,
                context:  [
                    'task_id'     => $task->id,
                    'task_title'  => $task->title,
                    'old_status'  => $existing->status,
                    'new_status'  => $task->status,
                    'actor_name'  => $authorName,
                ]
            );
        }

        // Notify on assignee change
        if ($existing->assigneeId !== $task->assigneeId && $task->assigneeId) {
            $this->notificationService->notifyOne(
                event:    'task.assigned',
                tenantId: $tenantId,
                userId:   $task->assigneeId,
                context:  [
                    'task_id'     => $task->id,
                    'task_title'  => $task->title,
                    'assignee_id' => $task->assigneeId,
                    'actor_name'  => $authorName,
                ]
            );
        }
    }

    private function calculateCompletedAt(TaskEntity $existing, UpdateTaskDTO $dto){
        // Auto-set completedAt when status transitions to done
        $completedAt = $existing->completedAt;
        if ($dto->status === 'done' && $existing->status !== 'done') {
            $completedAt = new \DateTime();
        } elseif ($dto->status !== 'done') {
            $completedAt = null;
        }

        return $completedAt; 
    }
}
