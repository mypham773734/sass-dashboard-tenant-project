<?php

namespace App\Application\Tenant\UseCases;

use App\Domain\TenantSetting\Repositories\TenantSettingRepositoryInterface;

class GetTenantSettingsUseCase
{
    public function __construct(
        private readonly TenantSettingRepositoryInterface $settingRepository,
    ) {}

    public function execute(int $tenantId): array
    {
        return $this->settingRepository->getAllForTenant($tenantId);
    }
}
    