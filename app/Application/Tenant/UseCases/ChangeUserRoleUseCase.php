<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Notification\Contracts\NotificationServiceInterface;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\Role\Repositories\RoleRepositoryInterface; 

class ChangeUserRoleUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly NotificationServiceInterface $notificationService,
        private readonly RoleRepositoryInterface $roleRepository
    ) {}

    public function execute(int $tenantId, int $userId, string $newRole): void
    {
        $oldRoleViaSpatie  = $this->roleRepository->getUserRoleForTenant($userId, $tenantId); 

        $this->roleRepository->assignUserRole($userId, $tenantId, $newRole); 

        // Notify affected user of role change
        $this->notifySystem($tenantId, $userId, $newRole, $oldRoleViaSpatie->name); 
    }

    private function notifySystem(int $tenantId, int $userId, string $newRole, string $oldRole){
        $user = $this->userRepository->findById($userId);
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
