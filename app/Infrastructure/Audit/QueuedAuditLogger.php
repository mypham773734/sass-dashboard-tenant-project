<?php

namespace App\Infrastructure\Audit;

use App\Application\Audit\AuditLoggerInterface;
use App\Infrastructure\Queue\Jobs\WriteAuditLogJob;

class QueuedAuditLogger implements AuditLoggerInterface
{
    public function log(
        string  $action,
        ?int    $entityId   = null,
        ?string $entityType = null,
        ?array  $newValues  = null,
        ?array  $oldValues  = null,
        ?array  $metadata   = null,
    ): void {
        if (! config('audit.enabled', true)) {
            return;
        }

        $tenantId = tenantContext()->getId(); 

        WriteAuditLogJob::dispatch([
            'tenant_id'   => $tenantId,
            'user_id'     => auth()->id(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'metadata'    => $metadata,
        ]);
    }
}
