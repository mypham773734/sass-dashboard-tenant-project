<?php

namespace App\DTOs\Tenants;

use DateTime;

class CreateTenantDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly bool $is_active,
        public readonly DateTime $trial_ends_at,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            is_active: $data['is_active'],
            trial_ends_at: $data['trial_ends_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'trial_ends_at' => $this->trial_ends_at,
        ];
    }
}

