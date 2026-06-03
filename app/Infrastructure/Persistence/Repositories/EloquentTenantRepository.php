<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Tenant\Entities\TenantEntity;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of TenantRepositoryInterface.
 *
 * This class is the only place in the entire codebase allowed to touch
 * the Tenant Eloquent model. Everything above (Domain, Application) only
 * sees TenantEntity.
 */
class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function findAllByUserId(int $userId, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
            ->paginate($perPage)
            ->through(fn (Tenant $model) => $this->toEntity($model));
    }

    public function findById(int $id): ?TenantEntity
    {
        $model = Tenant::withoutGlobalScopes()->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findBySlug(string $slug): ?TenantEntity
    {
        $model = Tenant::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function create(TenantEntity $entity): TenantEntity
    {
        $model = Tenant::create($this->toArray($entity));

        return $this->toEntity($model);
    }

    public function update(TenantEntity $entity): TenantEntity
    {
        $model = Tenant::withoutGlobalScopes()->findOrFail($entity->id);
        $model->update($this->toArray($entity));

        return $this->toEntity($model->fresh());
    }

    public function forceDelete(int $id): bool
    {
        return (bool) Tenant::withoutGlobalScopes()
            ->findOrFail($id)
            ->forceDelete();
    }

    public function attachUser(int $tenantId, int $userId, string $role): void
    {
        Tenant::withoutGlobalScopes()
            ->findOrFail($tenantId)
            ->users()
            ->attach($userId, ['role' => $role]);
    }

    public function detachAllUsers(int $tenantId): void
    {
        Tenant::withoutGlobalScopes()
            ->findOrFail($tenantId)
            ->users()
            ->detach();
    }

    public function hasUser(int $tenantId, int $userId): bool
    {
        return Tenant::withoutGlobalScopes()
            ->findOrFail($tenantId)
            ->users()
            ->where('users.id', $userId)
            ->exists();
    }

    // ── Mapping helpers ───────────────────────────────────────────────────────

    private function toEntity(Tenant $model): TenantEntity
    {
        return new TenantEntity(
            id:          $model->id,
            name:        $model->name,
            slug:        $model->slug,
            isActive:    (bool) $model->is_active,
            trialEndsAt: $model->trial_ends_at
                ? new \DateTime($model->trial_ends_at)
                : null,
            settings:    $model->settings,
        );
    }

    private function toArray(TenantEntity $entity): array
    {
        return [
            'name'          => $entity->name,
            'slug'          => $entity->slug,
            'is_active'     => $entity->isActive,
            'trial_ends_at' => $entity->trialEndsAt?->format('Y-m-d H:i:s'),
            'settings'      => $entity->settings,
        ];
    }
}
