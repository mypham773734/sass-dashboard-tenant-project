<?php

namespace App\Application\Tenant\DTOs;

/**
 * Carries validated input data for updating a Tenant.
 * Slug is intentionally excluded — it is looked up, not changed by the user.
 */
class UpdateTenantDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly bool    $isActive,
        public readonly ?\DateTimeInterface $trialEndsAt = null,
        public readonly ?array  $settings = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name'],
            isActive:    (bool) ($data['is_active'] ?? true),
            trialEndsAt: isset($data['trial_ends_at'])
                ? new \DateTime($data['trial_ends_at'])
                : null,
            settings:    $data['settings'] ?? null,
        );
    }
}
