<?php

namespace App\Application\User\UseCases;

use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Application\Audit\AuditLoggerInterface;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;

class ChangeTenantSelectedUseCase
{

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    public function execute(int $userId, int $tenantId): void
    {
        $user = $this->userRepository->findById($userId);

        // Check user belong to tenant
        $belongsToTenant = collect($user->tenants)->contains('id', $tenantId);
        if (! $belongsToTenant) {
            throw new \DomainException('Bạn không có quyền truy cập vào workspace này.');
        }

        // Check tenant active
        $tenant = $this->tenantRepository->findById($tenantId);
        if (! $tenant || ! $tenant->isActive) {
            throw new \DomainException('Workspace này hiện không hoạt động.');
        }

        $this->auditLogger->log(
            action:     'tenant.switched',
            entityId:   $tenantId,
            entityType: 'Tenant',
            metadata:   ['from_tenant_id' => $this->userRepository->getMeta($userId, 'tenant_id_selected')],
        );

        $this->userRepository->setMeta($userId, 'tenant_id_selected', (string) $tenantId);
    }
}
