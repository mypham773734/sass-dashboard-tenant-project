<?php 

use App\Shared\Tenant\TenantContext; 
use App\Shared\Auth\AuthContext; 
use App\Domain\Tenant\TenantSettingDefaults;
use App\Domain\TenantSetting\Repositories\TenantSettingRepositoryInterface;


if(!function_exists('authContext')){
    function authContext(){
        return app(AuthContext::class);
    }
}

if(!function_exists('tenantContext')){
    function tenantContext(){
        return app(TenantContext::class);
    }
}

if (!function_exists('tenantSetting')) {
    function tenantSetting(string $key, mixed $default = null, ?int $tenantId = null): mixed
    {
        $tenantId = $tenantId ?? tenantContext()->getId();
        $stored = app(TenantSettingRepositoryInterface::class)->getAllForTenant($tenantId);

        return data_get($stored, $key) ?? data_get(TenantSettingDefaults::DEFAULTS, $key, $default);
    }
}
