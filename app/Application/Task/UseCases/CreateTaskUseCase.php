<?php

namespace App\Application\Task\UseCases;

use App\Application\Task\DTOs\CreateTaskDTO;
use App\Domain\Task\Entities\TaskEntity;
use App\Domain\Task\Repositories\TaskRepositoryInterface;

class CreateTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function execute(CreateTaskDTO $dto, int $tenantId, int $createdBy): TaskEntity
    {
        $entity = new TaskEntity(
            id:          null,
            tenantId:    $tenantId,
            projectId:   $dto->projectId,
            createdBy:   $createdBy,
            assigneeId:  $dto->assigneeId,
            title:       $dto->title,
            description: $dto->description,
            status:      $dto->status,
            priority:    $dto->priority,
            order:       0,
            dueDate:     $dto->dueDate ? new \DateTime($dto->dueDate) : null,
            completedAt: null,
        );

        return $this->taskRepository->create($entity);
    }
}
