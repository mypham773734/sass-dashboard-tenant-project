<?php

namespace App\Domain\TenantSetting\Entities;

class TenantSettingEntity
{
    public function __construct(
        public readonly ?int   $id,
        public readonly int    $tenantId,
        public readonly string $key,
        public readonly mixed  $value,
    ) {}
}
