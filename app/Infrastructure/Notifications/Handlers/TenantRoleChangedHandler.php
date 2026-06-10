<?php

namespace App\Infrastructure\Notifications\Handlers;

use App\Application\Notification\DTOs\NotificationDTO;

class TenantRoleChangedHandler extends BaseNotificationHandler
{
    protected string $event = 'tenant.role_changed';
    protected array $requiredContext = ['target_user_id', 'target_user_name', 'old_role', 'new_role'];

    protected function resolveRecipients(int $tenantId, array $context): array
    {
        return [$context['target_user_id']];
    }

    protected function renderTitle(array $context): string
    {
        return "Your role changed from {$context['old_role']} to {$context['new_role']}";
    }

    protected function buildUrl(int $tenantId, array $context): string
    {
        return route('tenant.members') ?? '';
    }
}
