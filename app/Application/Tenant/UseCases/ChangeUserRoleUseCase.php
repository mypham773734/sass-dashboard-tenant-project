<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Notification\Contracts\NotificationServiceInterface;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;

class ChangeUserRoleUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    public function execute(int $tenantId, int $userId, string $newRole): void
    {
        $this->tenantRepository->updateUserRole($tenantId, $userId, $newRole);

        // Notify affected user of role change
        $this->notifySystem($tenantId, $userId, $newRole); 
    }

    private function notifySystem(int $tenantId, int $userId, string $newRole){
        $user = $this->userRepository->findById($userId);
        $oldRole = $this->tenantRepository->getUserRole($tenantId, $userId);
        $authorName = authContext()->getUser()->name; 
        $this->notificationService->notifyOne(
            event:    'tenant.role_changed',
            tenantId: $tenantId,
            userId:   $userId,
            context:  [
                'target_user_id'   => $userId,
                'target_user_name' => $user->name,
                'old_role'         => $oldRole,
                'new_role'         => $newRole,
                'actor_name'       => $authorName,
            ]
        );
    }
}
