<?php

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\Audit\Entities\AuditLog;
use App\Domain\Audit\Repositories\AuditRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class WriteAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // public string $queue; 

    public function __construct(private readonly array $data) {
        $this->onQueue('audit');
    }

    public function handle(AuditRepositoryInterface $repo): void
    {
        $repo->create(new AuditLog(
            id:         null,
            tenantId:   $this->data['tenant_id'] ?? null,
            userId:     $this->data['user_id'] ?? null,
            action:     $this->data['action'],
            entityType: $this->data['entity_type'] ?? null,
            entityId:   $this->data['entity_id'] ?? null,
            oldValues:  $this->data['old_values'] ?? null,
            newValues:  $this->data['new_values'] ?? null,
            ipAddress:  $this->data['ip_address'] ?? null,
            userAgent:  $this->data['user_agent'] ?? null,
            metadata:   $this->data['metadata'] ?? null,
            createdAt:  null,
        ));
    }
}
