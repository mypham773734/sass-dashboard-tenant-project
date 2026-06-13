<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{

    public function viewAny(User $user, Tenant $tenant){
        return $user->hasPermissionInTenant('tenant:view', $tenant->id);
    }   
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:view', $tenant->id);
    }

    public function edit(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:edit', $tenant->id);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:delete', $tenant->id);
    }

    public function inviteUser(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:invite_user', $tenant->id);
    }

    public function removeUser(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionInTenant('tenant:remove_user', $tenant->id);
    }

    public function viewAuditLog(User $user, Tenant $tenant): bool
    {
        return $user->isAdminOfTenant($tenant->id);
    }

    public function create(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('tenant:create', $tenantId);
    }
}
