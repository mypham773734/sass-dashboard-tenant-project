<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Notification\Contracts\NotificationServiceInterface;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;

class AttachUserToTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly NotificationServiceInterface $notificationService,
    ) {}

    public function execute(int $tenantId, int $userId, string $role = 'member'): void
    {
        $this->tenantRepository->attachUser($tenantId, $userId, $role);

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
