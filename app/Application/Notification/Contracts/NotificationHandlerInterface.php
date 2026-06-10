<?php

namespace App\Application\Notification\Contracts;

use App\Application\Notification\DTOs\NotificationDTO;

interface NotificationHandlerInterface
{
    public function handle(int $tenantId, array $context): NotificationDTO;
}
