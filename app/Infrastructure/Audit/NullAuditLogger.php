<?php

namespace App\Infrastructure\Audit;

use App\Application\Audit\AuditLoggerInterface;

class NullAuditLogger implements AuditLoggerInterface
{
    private array $logs = [];

    public function log(
        string  $action,
        ?int    $entityId   = null,
        ?string $entityType = null,
        ?array  $newValues  = null,
        ?array  $oldValues  = null,
        ?array  $metadata   = null,
    ): void {
        $this->logs[] = [
            'action'      => $action,
            'entity_id'   => $entityId,
            'entity_type' => $entityType,
            'new_values'  => $newValues,
            'old_values'  => $oldValues,
            'metadata'    => $metadata,
        ];
    }

    public function assertLogged(string $action): bool
    {
        return collect($this->logs)->contains('action', $action);
    }

    public function assertNotLogged(string $action): bool
    {
        return ! $this->assertLogged($action);
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function reset(): void
    {
        $this->logs = [];
    }
}
