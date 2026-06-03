<?php

namespace App\Application\Project\UseCases;

use App\Domain\Project\Repositories\ProjectRepositoryInterface;

class DeleteProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    public function execute(int $id, int $tenantId): bool
    {
        $project = $this->projectRepository->findById($id, $tenantId);

        if ($project === null) {
            throw new \DomainException("Project [{$id}] not found in this tenant.");
        }

        return $this->projectRepository->delete($id, $tenantId);
    }
}
