<?php

namespace App\Infrastructure\Notifications\Handlers;

use App\Application\Notification\DTOs\NotificationDTO;
use App\Domain\User\Repositories\UserRepositoryInterface;

class TenantMemberRemovedHandler extends BaseNotificationHandler
{
    protected string $event = 'tenant.member_removed';
    protected array $requiredContext = ['removed_user_id', 'removed_user_name'];

    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    protected function resolveRecipients(int $tenantId, array $context): array
    {
        $adminIds = $this->userRepo->findAdminsByTenant($tenantId);
        $removedUserId = $context['removed_user_id'];

        // Notify admins + the removed user (if not already removed)
        return array_unique(array_merge($adminIds, [$removedUserId]));
    }

    protected function renderTitle(array $context): string
    {
        return "{$context['removed_user_name']} was removed from the workspace";
    }

    protected function buildUrl(int $tenantId, array $context): string
    {
        return route('tenant.members') ?? '';
    }
}
