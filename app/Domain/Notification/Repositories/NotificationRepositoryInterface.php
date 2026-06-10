<?php

namespace App\Domain\Notification\Repositories;

use App\Application\Notification\DTOs\CreateNotificationDTO;
use App\Domain\Notification\Entities\NotificationEntity;
use Carbon\Carbon;

interface NotificationRepositoryInterface
{
    public function createForUser(
        CreateNotificationDTO $dto,
        int $userId,
        int $tenantId
    ): NotificationEntity;

    public function getUnreadByUser(int $userId, int $tenantId, int $limit = 10): array;

    public function countUnreadByUser(int $userId, int $tenantId): int;

    public function markAsRead(int $notificationId, int $userId): void;

    public function markAllAsRead(int $userId, int $tenantId): void;

    public function deleteOlderThan(int $tenantId, Carbon $before): int;
}
