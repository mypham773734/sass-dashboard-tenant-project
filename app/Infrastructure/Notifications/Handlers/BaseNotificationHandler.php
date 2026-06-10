<?php

namespace App\Infrastructure\Notifications\Handlers;

use App\Application\Notification\Contracts\NotificationHandlerInterface;
use App\Application\Notification\DTOs\NotificationDTO;

abstract class BaseNotificationHandler implements NotificationHandlerInterface
{
    protected string $event;
    protected array $requiredContext = [];

    final public function handle(int $tenantId, array $context): NotificationDTO
    {
        $this->assertContextComplete($context);

        return new NotificationDTO(
            event:        $this->event,
            recipientIds: $this->resolveRecipients($tenantId, $context),
            title:        $this->renderTitle($context),
            body:         $this->renderBody($tenantId, $context),
            url:          $this->buildUrl($tenantId, $context),
            data:         $context,
        );
    }

    abstract protected function resolveRecipients(int $tenantId, array $context): array;

    abstract protected function renderTitle(array $context): string;

    protected function renderBody(int $tenantId, array $context): ?string
    {
        return null;
    }

    protected function buildUrl(int $tenantId, array $context): string
    {
        return '';
    }

    private function assertContextComplete(array $context): void
    {
        $missing = array_diff($this->requiredContext, array_keys($context));

        if ($missing) {
            throw new \InvalidArgumentException(
                "Missing context for {$this->event}: " . implode(', ', $missing)
            );
        }
    }
}
