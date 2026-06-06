<?php

namespace App\Domain\Audit\Repositories;

use App\Domain\Audit\Entities\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditRepositoryInterface
{
    public function create(AuditLog $auditLog): void;

    public function paginateByTenant(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
