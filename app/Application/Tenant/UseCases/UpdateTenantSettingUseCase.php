<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Tenant\DTOs\UpdateTenantSettingDTO;
use App\Domain\Tenant\TenantSettingDefaults;
use App\Domain\TenantSetting\Repositories\TenantSettingRepositoryInterface;

class UpdateTenantSettingUseCase
{
    public function __construct(
        private readonly TenantSettingRepositoryInterface $settingRepository,
    ) {}

    public function execute(int $tenantId, UpdateTenantSettingDTO $dto): void
    {
        if (!array_key_exists($dto->section, TenantSettingDefaults::DEFAULTS)) {
            throw new \DomainException("Unknown settings section: {$dto->section}");
        }

        $pairs = [];
        foreach ($dto->values as $key => $value) {
            $pairs["{$dto->section}.{$key}"] = $value;
        }

        $this->settingRepository->upsertMany($tenantId, $pairs);
    }
}
