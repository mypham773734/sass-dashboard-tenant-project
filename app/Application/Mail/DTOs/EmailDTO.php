<?php

namespace App\Application\Mail\DTOs;

class EmailDTO
{
    public function __construct(
        public readonly string $type,
        public readonly string $subject,
        public readonly array  $recipients,
        public readonly string $template,
        public readonly array  $data = [],
    ) {}
}
