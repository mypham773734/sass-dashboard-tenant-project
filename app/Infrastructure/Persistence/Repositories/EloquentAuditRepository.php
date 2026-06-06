<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Audit\Entities\AuditLog;
use App\Domain\Audit\Repositories\AuditRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentAuditRepository implements AuditRepositoryInterface
{
    public function create(AuditLog $entity): void
    {
        \App\Models\AuditLog::create($this->toArray($entity));
    }

    public function paginateByTenant(int $tenantId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return \App\Models\AuditLog::query()
            ->where('tenant_id', $tenantId)
            ->when($filters['user_id'] ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['action']  ?? null, fn($q, $v) => $q->where('action', $v))
            ->when($filters['from']    ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to']      ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    private function toArray(AuditLog $entity): array
    {
        return [
            'tenant_id'   => $entity->tenantId,
            'user_id'     => $entity->userId,
            'action'      => $entity->action,
            'entity_type' => $entity->entityType,
            'entity_id'   => $entity->entityId,
            'old_values'  => $entity->oldValues,
            'new_values'  => $entity->newValues,
            'ip_address'  => $entity->ipAddress,
            'user_agent'  => $entity->userAgent,
            'metadata'    => $entity->metadata,
        ];
    }

    private function toEntity(\App\Models\AuditLog $model): AuditLog
    {
        return new AuditLog(
            id:         $model->id,
            tenantId:   $model->tenant_id,
            userId:     $model->user_id,
            action:     $model->action,
            entityType: $model->entity_type,
            entityId:   $model->entity_id,
            oldValues:  $model->old_values,
            newValues:  $model->new_values,
            ipAddress:  $model->ip_address,
            userAgent:  $model->user_agent,
            metadata:   $model->metadata,
            createdAt:  $model->created_at?->toDateTimeString(),
        );
    }
}
