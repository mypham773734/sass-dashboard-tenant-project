<?php

namespace App\Services\Impl;

use App\Services\Contracts\TenantServiceInterface;
use App\DTOs\Tenants\CreateTenantDTO;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantService implements TenantServiceInterface
{
    public function createTenant(CreateTenantDTO $dto)
    {
        $data = $dto->toArray();
        return Tenant::create($data);
    }

    public function addUserToTenant(int $tenantId, int $userId, string $role = 'admin')
    {
        throw new \Exception('Not implemented');
    }

    public function updateTenant(Tenant $tenant, CreateTenantDTO $dto)
    {
        $data = $dto->toArray();
        $tenant->update($data);
        return $tenant;
    }

    public function deleteTenant(Tenant $tenant, int $userId)
    {
        return DB::transaction(function () use ($tenant, $userId) {
            $isExist = $tenant->users()->where('id', $userId)->exists();

            if (!$isExist) {
                throw new \Exception('User is not associated with this tenant');
            }


            // Detach all users from the tenant
            $tenant->users()->detach();

            // Delete the tenant
            $tenant->forceDelete();
            
            return true;
        });
    }
}
