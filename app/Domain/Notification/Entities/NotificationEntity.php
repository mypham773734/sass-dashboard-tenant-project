<?php

namespace App\Domain\Notification\Entities;

class NotificationEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $event,
        public readonly string $title,
        public readonly ?string $body,
        public readonly ?string $url,
        public readonly bool $isRead,
        public readonly ?string $readAt,
        public readonly array $data,
        public readonly string $createdAt,
    ) {}
}
