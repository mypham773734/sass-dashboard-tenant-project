<?php

namespace App\Application\Notification\DTOs;

class CreateNotificationDTO
{
    public function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly ?string $body = null,
        public readonly ?string $url = null,
        public readonly array $data = [],
    ) {}
}
