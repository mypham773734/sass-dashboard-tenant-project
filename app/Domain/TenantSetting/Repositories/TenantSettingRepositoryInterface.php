<?php

namespace App\Domain\TenantSetting\Repositories;

interface TenantSettingRepositoryInterface
{
    /**
     * Return all stored settings for a tenant as a flat [dot.key => value] map.
     * Merged with defaults (stored values override defaults).
     */
    public function getAllForTenant(int $tenantId): array;

    /**
     * Upsert multiple [dot.key => value] pairs for a tenant in one atomic operation.
     * Cache is flushed after write.
     */
    public function upsertMany(int $tenantId, array $pairs): void;
}
