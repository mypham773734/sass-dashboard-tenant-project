<?php

namespace App\Application\Audit\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Domain\Audit\Repositories\AuditRepositoryInterface;

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
