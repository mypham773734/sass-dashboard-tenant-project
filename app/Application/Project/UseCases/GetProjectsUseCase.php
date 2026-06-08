<?php

namespace App\Application\Project\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Domain\Project\Repositories\ProjectRepositoryInterface;

class GetProjectsUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    public function execute(int $tenantId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->projectRepository->findAllByTenantId($tenantId, $perPage);
    }
}
