<?php

namespace App\Services\Impl; 

use App\Services\Contracts\TenantServiceInterface;

class TenantService implements TenantServiceInterface{
    public function createTenant(array $data)
    {
        throw new \Exception('Not implemented');
    }

    public function addUserToTenant(int $tenantId, int $userId, string $role = 'admin')
    {
        throw new \Exception('Not implemented');
    }
}