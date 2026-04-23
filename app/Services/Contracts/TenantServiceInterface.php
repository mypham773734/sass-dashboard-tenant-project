<?php 

namespace App\Services\Contracts; 

use App\DTOs\Tenants\CreateTenantDTO;

use App\Models\Tenant; 

interface TenantServiceInterface{
    public function getTenants($limit = 10); 
    public function createTenant(CreateTenantDTO $dto); 

    public function addUserToTenant(int $tenantId, int $userId, string $role = 'admin'); 

    public function updateTenant(Tenant $tenant, CreateTenantDTO $dto); 

    public function deleteTenant(Tenant $tenant, int $userId);
}