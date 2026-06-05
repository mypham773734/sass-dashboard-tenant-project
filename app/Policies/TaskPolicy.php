<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('task:view', $tenantId);
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->hasPermissionInTenant('task:view_all', $task->tenant_id)) {
            return true;
        }

        return $user->hasPermissionInTenant('task:view_own', $task->tenant_id)
            && ($task->created_by === $user->id || $task->assignee_id === $user->id);
    }

    public function create(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('task:create', $tenantId);
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->hasPermissionInTenant('task:edit_all', $task->tenant_id)) {
            return true;
        }

        return $user->hasPermissionInTenant('task:edit_own', $task->tenant_id)
            && $task->created_by === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->hasPermissionInTenant('task:delete_all', $task->tenant_id)) {
            return true;
        }

        return $user->hasPermissionInTenant('task:delete_own', $task->tenant_id)
            && $task->created_by === $user->id;
    }

    public function updateStatus(User $user, Task $task): bool
    {
        return $user->hasPermissionInTenant('task:edit_status', $task->tenant_id);
    }

    public function assign(User $user, Task $task): bool
    {
        return $user->hasPermissionInTenant('task:assign', $task->tenant_id);
    }
}
