<?php

namespace App\Infrastructure\Notifications;

use App\Application\Notification\Contracts\NotificationServiceInterface;
use App\Infrastructure\Notifications\Jobs\WriteNotificationJob;

class NotificationService implements NotificationServiceInterface
{
    public function notifyOne(
        string $event,
        int $tenantId,
        int $userId,
        array $context = []
    ): void {
        if (!config('notification.enabled', true)) {
            return;
        }

        if (!$this->isEventEnabled($event)) {
            return;
        }

        $context['__event__'] = $event;

        WriteNotificationJob::dispatch($event, $tenantId, $userId, $context)
            ->onQueue(config('notification.queue', 'notifications'));
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

    private function isEventEnabled(string $event): bool
    {
        return config("notification.event_types.{$event}.enabled", true);
    }
}
