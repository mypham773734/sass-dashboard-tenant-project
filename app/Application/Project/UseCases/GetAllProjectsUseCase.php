<?php

namespace App\Application\Project\UseCases;

use App\Domain\Project\Repositories\ProjectRepositoryInterface;
use Illuminate\Support\Collection;

class GetAllProjectsUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    public function execute(int $tenantId): Collection
    {
        return $this->projectRepository->getAllByTenantId($tenantId);
    }
}
