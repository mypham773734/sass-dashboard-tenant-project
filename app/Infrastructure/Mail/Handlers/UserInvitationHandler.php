<?php

namespace App\Infrastructure\Mail\Handlers;

use App\Application\Mail\Contracts\EmailHandlerInterface;
use App\Application\Mail\DTOs\EmailDTO;
use Carbon\Carbon;

class UserInvitationHandler implements EmailHandlerInterface
{
    public function handle(int $tenantId, array $context): EmailDTO
    {
        $this->assertContext($context);

        return new EmailDTO(
            type:       'user_invitation',
            subject:    "You've been invited to join {$context['tenant_name']}",
            recipients: [$context['invited_email']],
            template:   'emails.user-invitation',
            data:       [
                'invitedEmail' => $context['invited_email'],
                'invitedBy'    => $context['invited_by'],
                'tenantName'   => $context['tenant_name'],
                'acceptUrl'    => $context['accept_url'],
                'expiresAt'    => $context['expires_at'] ?? null,
            ],
        );
    }

    public function shouldSend(string $schedule, Carbon $now): bool
    {
        // On-demand only — không có schedule
        return false;
    }

    private function assertContext(array $context): void
    {
        $required = ['invited_email', 'invited_by', 'tenant_name', 'accept_url'];

        foreach ($required as $key) {
            if (empty($context[$key])) {
                throw new \InvalidArgumentException(
                    "UserInvitationHandler missing required context key: [{$key}]"
                );
            }
        }
    }
}
