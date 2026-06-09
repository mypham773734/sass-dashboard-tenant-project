<?php

namespace App\Domain\Audit\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Domain\Audit\Entities\AuditLog;

interface AuditRepositoryInterface
{
    public function create(AuditLog $auditLog): void;

    public function paginateByTenant(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /** @return AuditLog[] */
    public function getRecentByTenant(int $tenantId, \Carbon\Carbon $since, int $limit = 50): array;
}
