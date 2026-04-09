<?php 

namespace App\Services\Contracts; 

interface TenantServiceInterface{
    public function createTenant(array $data); 

    public function addUserToTenant(int $tenantId, int $userId, string $role = 'admin'); 
}