<?php

namespace App\Application\Project\UseCases;

use App\Application\Project\DTOs\UpdateProjectDTO;
use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Project\Repositories\ProjectRepositoryInterface;

class UpdateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    public function execute(int $id, int $tenantId, UpdateProjectDTO $dto): ProjectEntity
    {
        $existing = $this->projectRepository->findById($id, $tenantId);

        if ($existing === null) {
            throw new \DomainException("Project [{$id}] not found in this tenant.");
        }

        $updated = new ProjectEntity(
            id:          $existing->id,
            tenantId:    $existing->tenantId,
            ownerId:     $existing->ownerId,
            name:        $dto->name,
            status:      $dto->status,
            description: $dto->description,
        );

        return $this->projectRepository->update($updated);
    }
}
