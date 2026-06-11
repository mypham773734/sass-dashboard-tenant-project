<?php

namespace App\Infrastructure\Notifications\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public readonly int $notificationId,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $url,
        public readonly \Carbon\Carbon $createdAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->tenantId}.user.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification-created';
    }

    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'title'           => $this->title,
            'body'            => $this->body,
            'url'             => $this->url,
            'created_at'      => $this->createdAt->toIso8601String(),
        ];
    }

    public function broadcastQueue(): string
    {
        return config('notification.queue', 'notifications');
    }
}
