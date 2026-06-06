<?php

namespace App\Application\Audit\UseCases;

use App\Domain\Audit\Repositories\AuditRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetAuditLogsUseCase
{
    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository,
    ) {}

    public function execute(int $tenantId, array $filters = []): LengthAwarePaginator
    {
        return $this->auditRepository->paginateByTenant($tenantId, $filters);
    }
}
