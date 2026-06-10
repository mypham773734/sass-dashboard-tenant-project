<?php

namespace App\Application\Notification\Contracts;

interface NotificationServiceInterface
{
    public function notifyOne(
        string $event,
        int $tenantId,
        int $userId,
        array $context = []
    ): void;

    public function notify(
        string $event,
        int $tenantId,
        array $recipientIds,
        array $context = []
    ): void;
}
