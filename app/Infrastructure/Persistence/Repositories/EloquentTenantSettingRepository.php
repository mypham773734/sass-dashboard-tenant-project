<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Tenant\TenantSettingDefaults;
use App\Domain\TenantSetting\Repositories\TenantSettingRepositoryInterface;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\Cache;

class EloquentTenantSettingRepository implements TenantSettingRepositoryInterface
{
    private const int TTL = 600;

    public function getAllForTenant(int $tenantId): array
    {
        $cacheTag = "tenant:{$tenantId}:settings";
        $cacheKey = "tenant_settings:{$tenantId}";

        return Cache::tags([$cacheTag])->remember($cacheKey, self::TTL, function () use ($tenantId) {
            $flatStored = TenantSetting::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->pluck('value', 'key')
                ->toArray();

            // Convert flat dot-notation keys to nested array for merging
            $stored = [];
            foreach ($flatStored as $dotKey => $value) {
                data_set($stored, $dotKey, $value);
            }

            return array_replace_recursive(
                TenantSettingDefaults::DEFAULTS,
                $stored
            );
        });
    }

    public function upsertMany(int $tenantId, array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            TenantSetting::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => $key],
                ['value' => $value],
            );
        }

        Cache::tags(["tenant:{$tenantId}:settings"])->flush();
    }
}
