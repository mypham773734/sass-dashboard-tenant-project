<?php

namespace App\Application\Project\UseCases;

use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Project\Repositories\ProjectRepositoryInterface;

class FindProjectByIdUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    public function execute(int $id, int $tenantId): ?ProjectEntity
    {
        return $this->projectRepository->findById($id, $tenantId);
    }
}
