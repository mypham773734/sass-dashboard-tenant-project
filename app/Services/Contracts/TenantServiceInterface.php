<?php 

namespace App\Services\Contracts; 

use App\DTOs\Tenants\CreateTenantDTO;

interface TenantServiceInterface{
    public function createTenant(CreateTenantDTO $dto); 

    public function addUserToTenant(int $tenantId, int $userId, string $role = 'admin'); 
}