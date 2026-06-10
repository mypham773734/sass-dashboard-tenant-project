<?php

namespace App\Infrastructure\Notifications\Handlers;

use App\Application\Notification\DTOs\NotificationDTO;
use App\Domain\User\Repositories\UserRepositoryInterface;

class TenantMemberAddedHandler extends BaseNotificationHandler
{
    protected string $event = 'tenant.member_added';
    protected array $requiredContext = ['new_user_name', 'new_user_id'];

    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    protected function resolveRecipients(int $tenantId, array $context): array
    {
        return $this->userRepo->findAdminsByTenant($tenantId);
    }

    protected function renderTitle(array $context): string
    {
        return "{$context['new_user_name']} joined the workspace";
    }

    protected function buildUrl(int $tenantId, array $context): string
    {
        return route('tenant.members') ?? '';
    }
}
