<?php

namespace App\Domain\Audit\Entities;

class AuditLog
{
    public function __construct(
        public readonly ?int    $id,
        public readonly ?int    $tenantId,
        public readonly ?int    $userId,
        public readonly string  $action,
        public readonly ?string $entityType,
        public readonly ?int    $entityId,
        public readonly ?array  $oldValues,
        public readonly ?array  $newValues,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly ?array  $metadata,
        public readonly ?string $createdAt,
    ) {}
}
