<?php

namespace App\Application\Tenant\DTOs;

/**
 * Carries validated input data for creating a Tenant.
 * Property names follow PHP conventions (camelCase), not DB column names.
 */
class CreateTenantDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $slug,
        public readonly bool    $isActive,
        public readonly ?\DateTimeInterface $trialEndsAt = null,
        public readonly ?array  $settings = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name'],
            slug:        $data['slug'],
            isActive:    (bool) ($data['is_active'] ?? true),
            trialEndsAt: isset($data['trial_ends_at'])
                ? new \DateTime($data['trial_ends_at'])
                : null,
            settings:    $data['settings'] ?? null,
        );
    }
}
