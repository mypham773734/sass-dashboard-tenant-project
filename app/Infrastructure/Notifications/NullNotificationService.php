<?php

namespace App\Infrastructure\Notifications;

use App\Application\Notification\Contracts\NotificationServiceInterface;
use Illuminate\Support\Collection;

class NullNotificationService implements NotificationServiceInterface
{
    private array $sent = [];

    public function notifyOne(
        string $event,
        int $tenantId,
        int $userId,
        array $context = []
    ): void {
        $this->sent[] = compact('event', 'tenantId', 'userId', 'context');
    }

    public function notify(
        string $event,
        int $tenantId,
        array $recipientIds,
        array $context = []
    ): void {
        foreach ($recipientIds as $userId) {
            $this->notifyOne($event, $tenantId, $userId, $context);
        }
    }

    public function assertNotified(string $event, ?int $userId = null): bool
    {
        return collect($this->sent)->contains(function ($item) use ($event, $userId) {
            if ($item['event'] !== $event) {
                return false;
            }

            if ($userId !== null && $item['userId'] !== $userId) {
                return false;
            }

            return true;
        });
    }

    public function getSent(): array
    {
        return $this->sent;
    }

    public function reset(): void
    {
        $this->sent = [];
    }
}
