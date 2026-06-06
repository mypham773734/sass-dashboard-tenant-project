<?php

namespace App\Application\Project\UseCases;

use App\Application\Audit\AuditLoggerInterface;
use App\Application\Project\DTOs\UpdateProjectDTO;
use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Project\Repositories\ProjectRepositoryInterface;

class UpdateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly AuditLoggerInterface       $audit,
    ) {}

    public function execute(int $id, int $tenantId, UpdateProjectDTO $dto): ProjectEntity
    {
        $existing = $this->projectRepository->findById($id, $tenantId);

        if ($existing === null) {
            throw new \DomainException("Project [{$id}] not found in this tenant.");
        }

        $oldValues = [
            'name'        => $existing->name,
            'status'      => $existing->status,
            'description' => $existing->description,
        ];

        $updated = new ProjectEntity(
            id:          $existing->id,
            tenantId:    $existing->tenantId,
            ownerId:     $existing->ownerId,
            name:        $dto->name,
            status:      $dto->status,
            description: $dto->description,
        );

        $project = $this->projectRepository->update($updated);

        $this->audit->log(
            action:     'project.updated',
            entityId:   $project->id,
            entityType: 'Project',
            newValues:  [
                'name'        => $project->name,
                'status'      => $project->status,
                'description' => $project->description,
            ],
            oldValues:  $oldValues,
        );

        return $project;
    }
}
