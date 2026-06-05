<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('project:view', $tenantId);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasPermissionInTenant('project:view', $project->tenant_id);
    }

    public function create(User $user, int $tenantId): bool
    {
        return $user->hasPermissionInTenant('project:create', $tenantId);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermissionInTenant('project:edit', $project->tenant_id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermissionInTenant('project:delete', $project->tenant_id);
    }
}
