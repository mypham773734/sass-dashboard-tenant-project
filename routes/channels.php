<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Multi-tenant notification channel
// Format: tenant.{tenantId}.user.{userId}
Broadcast::channel('tenant.{tenantId}.user.{userId}', function ($user, $tenantId, $userId) {
    // Verify user ID matches route parameter
    if ((int) $userId !== $user->id) {
        return false;
    }

    // Verify user belongs to tenant (check pivot table)
    $belongsToTenant = $user->tenants()
        ->wherePivot('tenant_id', (int) $tenantId)
        ->exists();

    return $belongsToTenant ? $user : false;
});
