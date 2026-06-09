<?php

namespace App\Infrastructure\Mail\Handlers;

use App\Application\Mail\Contracts\EmailHandlerInterface;
use App\Application\Mail\DTOs\EmailDTO;
use App\Models\User;
use Carbon\Carbon;

class TenantNotificationHandler implements EmailHandlerInterface
{
    public function handle(int $tenantId, array $context): EmailDTO
    {
        $this->assertContext($context);

        $recipients = $this->resolveAdminEmails($tenantId);

        if (empty($recipients)) {
            throw new \DomainException(
                "TenantNotificationHandler: no admin/owner found for tenant [{$tenantId}]"
            );
        }

        return new EmailDTO(
            type:       'tenant_notification',
            subject:    "[{$context['tenant_name']}] {$context['event_title']}",
            recipients: $recipients,
            template:   'emails.tenant-notification',
            data:       [
                'tenantName'  => $context['tenant_name'],
                'eventTitle'  => $context['event_title'],
                'eventType'   => $context['event_type'],
                'description' => $context['description'],
                'actorName'   => $context['actor_name'],
                'occurredAt'  => $context['occurred_at'] ?? now()->toDateTimeString(),
                'actionUrl'   => $context['action_url'] ?? null,
                'actionLabel' => $context['action_label'] ?? 'View Details',
            ],
        );
    }

    public function shouldSend(string $schedule, Carbon $now): bool
    {
        // On-demand only — không có schedule
        return false;
    }

    private function resolveAdminEmails(int $tenantId): array
    {
        return User::whereHas('tenants', fn($q) => $q->where('tenants.id', $tenantId))
            ->get()
            ->filter(fn($user) => $user->isAdminOfTenant($tenantId))
            ->pluck('email')
            ->toArray();
    }

    private function assertContext(array $context): void
    {
        $required = ['tenant_name', 'event_title', 'event_type', 'description', 'actor_name'];

        foreach ($required as $key) {
            if (empty($context[$key])) {
                throw new \InvalidArgumentException(
                    "TenantNotificationHandler missing required context key: [{$key}]"
                );
            }
        }
    }
}
