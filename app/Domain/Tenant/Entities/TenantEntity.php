<?php

namespace App\Domain\Tenant\Entities;

/**
 * Pure domain object — no Laravel, no Eloquent.
 * Carries Tenant data and owns the business rules about a Tenant.
 */
class TenantEntity
{
    public function __construct(
        public readonly ?int    $id,
        public readonly string  $name,
        public readonly string  $slug,
        public readonly bool    $isActive,
        public readonly ?\DateTimeInterface $trialEndsAt = null,
        public readonly ?array  $settings = null,
    ) {}

    // ── Business Rules ────────────────────────────────────────────────────────

    /**
     * True while the trial period has not yet ended.
     */
    public function isOnTrial(): bool
    {
        if ($this->trialEndsAt === null) {
            return false;
        }

        return new \DateTime() < $this->trialEndsAt;
    }

    /**
     * True when the trial period has passed.
     */
    public function isTrialExpired(): bool
    {
        if ($this->trialEndsAt === null) {
            return false;
        }

        return new \DateTime() >= $this->trialEndsAt;
    }

    /**
     * A tenant is usable when it is active AND (has no trial OR trial is still running).
     */
    public function isUsable(): bool
    {
        return $this->isActive && ! $this->isTrialExpired();
    }
}
