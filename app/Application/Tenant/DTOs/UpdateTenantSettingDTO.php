<?php

namespace App\Application\Tenant\DTOs;

class UpdateTenantSettingDTO
{
    public function __construct(
        public readonly string $section,
        public readonly array $values,
    ) {}

    public static function fromArray(string $section, array $data): self
    {
        return new self(
            section: $section,
            values: $data[$section] ?? [],
        );
    }
}
