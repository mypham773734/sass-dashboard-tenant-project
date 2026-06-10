<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Notification\Contracts\NotificationServiceInterface;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;

class DetachUserFromTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    public function execute(int $tenantId, int $userId): void
    {
        $removedUser = $this->userRepository->findById($userId);

        $this->tenantRepository->detachUser($tenantId, $userId);

        $this->notifySystem($tenantId, $userId, $removedUser);
    }

    private function notifySystem(int $tenantId, int $userId, mixed $removedUser): void
    {
        // Notify admins and removed user
        $adminIds = $this->userRepository->findAdminsByTenant($tenantId);
        $recipientIds = array_unique([...$adminIds, $userId]);
        $authorName = authContext()->getUser()->name; 

        $this->notificationService->notify(
            event:        'tenant.member_removed',
            tenantId:     $tenantId,
            recipientIds: $recipientIds,
            context:      [
                'removed_user_id'   => $userId,
                'removed_user_name' => $removedUser->name,
                'actor_name'        => $authorName,
            ]
        );
    }
}
