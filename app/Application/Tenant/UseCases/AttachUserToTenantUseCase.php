<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Notification\Contracts\NotificationServiceInterface;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\Role\Repositories\RoleRepositoryInterface; 

class AttachUserToTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly NotificationServiceInterface $notificationService,
        private readonly RoleRepositoryInterface $roleRepository
    ) {}

    public function execute(int $tenantId, int $userId, string $role = 'member'): void
    {
        // Tenant membership và RBAC role là 2 hệ thống riêng — tenant_user chỉ còn quản lý membership
        $this->tenantRepository->attachUserWithTenant($tenantId, $userId);
        $this->roleRepository->assignUserRole($userId, $tenantId, $role); 

        // Notify admins that a new member joined
        $this->notifySystem($tenantId, $userId);
    }

    private function notifySystem(int $tenantId, int $userId)
    {
        $newUser = $this->userRepository->findById($userId);
        $authorName = authContext()->getUser()->name;
        $recipientIds = $this->userRepository->findAdminsByTenant($tenantId);
        $this->notificationService->notify(
            event: 'tenant.member_added',
            tenantId: $tenantId,
            recipientIds: $recipientIds,
            context: [
                'new_user_id'   => $userId,
                'new_user_name' => $newUser->name,
                'actor_name'    => $authorName,
            ]
        );
    }
}
