<?php

namespace App\Application\Project\UseCases;

use App\Application\Audit\AuditLoggerInterface;
use App\Application\Project\DTOs\CreateProjectDTO;
use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Project\Repositories\ProjectRepositoryInterface;

class CreateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly AuditLoggerInterface       $audit,
    ) {}

    public function execute(CreateProjectDTO $dto, int $tenantId, int $ownerId): ProjectEntity
    {
        $entity = new ProjectEntity(
            id:          null,
            tenantId:    $tenantId,
            ownerId:     $ownerId,
            name:        $dto->name,
            status:      $dto->status,
            description: $dto->description,
        );

        $project = $this->projectRepository->create($entity);

        $this->audit->log(
            action:     'project.created',
            entityId:   $project->id,
            entityType: 'Project',
            newValues:  [
                'name'        => $project->name,
                'status'      => $project->status,
                'description' => $project->description,
            ],
        );

        return $project;
    }
}
