<?php

namespace App\Services\Impl; 

use App\Services\Contracts\TenantServiceInterface;
use App\DTOs\Tenants\CreateTenantDTO;
use App\Models\Tenant; 

class TenantService implements TenantServiceInterface{
    public function createTenant(CreateTenantDTO $dto)
    {
        $data = $dto->toArray(); 
        return Tenant::create($data);
    }

    public function addUserToTenant(int $tenantId, int $userId, string $role = 'admin')
    {
        throw new \Exception('Not implemented');
    }
}