<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Notification\DTOs\CreateNotificationDTO;
use App\Domain\Notification\Entities\NotificationEntity;
use App\Domain\Notification\Repositories\NotificationRepositoryInterface;
use App\Models\Notification;
use Carbon\Carbon;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function createForUser(
        CreateNotificationDTO $dto,
        int $userId,
        int $tenantId
    ): NotificationEntity {
        $model = Notification::create([
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
            'event'     => $dto->event,
            'title'     => $dto->title,
            'body'      => $dto->body,
            'url'       => $dto->url,
            'is_read'   => false,
            'data'      => $dto->data,
        ]);

        return $this->toEntity($model);
    }

    public function getUnreadByUser(int $userId, int $tenantId, int $limit = 10): array
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->toArray();
    }

    public function countUnreadByUser(int $userId, int $tenantId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead(int $notificationId, int $userId): void
    {
        Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function markAllAsRead(int $userId, int $tenantId): void
    {
        Notification::query()
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function deleteOlderThan(int $tenantId, Carbon $before): int
    {
        return Notification::where('tenant_id', $tenantId)
            ->where('created_at', '<', $before)
            ->delete();
    }

    private function toEntity(Notification $model): NotificationEntity
    {
        return new NotificationEntity(
            id:        $model->id,
            userId:    $model->user_id,
            tenantId:  $model->tenant_id,
            event:     $model->event,
            title:     $model->title,
            body:      $model->body,
            url:       $model->url,
            isRead:    $model->is_read,
            readAt:    $model->read_at?->toDateTimeString(),
            data:      $model->data ?? [],
            createdAt: $model->created_at->toDateTimeString(),
        );
    }
}
