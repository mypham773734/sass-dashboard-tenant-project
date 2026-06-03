<?php

namespace App\Application\Project\DTOs;

class UpdateProjectDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $status,
        public readonly ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name'],
            status:      $data['status'] ?? 'active',
            description: $data['description'] ?? null,
        );
    }
}
