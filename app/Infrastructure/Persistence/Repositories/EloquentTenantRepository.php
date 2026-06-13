<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Tenant\Entities\TenantEntity;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class EloquentTenantRepository implements TenantRepositoryInterface
{
    private const int TTL_SHORT  = 300;
    private const int TTL_MEDIUM = 600;
    private const int TTL_LONG   = 900;

    public function findPaginatedByUserId(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        $page = request()->input('page', 1);
        $cacheTag = "user:{$userId}:tenants"; 
        $cacheKey = "user:{$userId}:tenants:page:{$page}:per:{$perPage}"; 

        $cached = Cache::tags([$cacheTag])
            ->remember($cacheKey, self::TTL_SHORT, function () use ($userId, $perPage) {
                $paginator = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                    ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
                    ->paginate($perPage);
                return [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'items'        => collect($paginator->items())->map(fn (Tenant $m) => $m->toArray())->all(),
                ];
            });

        $items = collect($cached['items'])->map(fn (array $row) => $this->toEntityFromArray($row));

        return new LengthAwarePaginator(
            $items,
            $cached['total'],
            $cached['per_page'],
            $cached['current_page'],
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function findAllByUserId(int $userIds){
        return Tenant::whereHas('users', fn ($q) => $q->where('users.id', $userIds))->get(); 
    }

    public function findById(int $id): ?TenantEntity
    {  
        $cacheTag = "tenant:{$id}"; 
        $cacheKey = "tenant:id:{$id}"; 

        $data = Cache::tags([$cacheTag])
            ->remember($cacheKey, self::TTL_MEDIUM, function () use ($id) {
                return Tenant::withoutGlobalScopes()->find($id)?->toArray();
            });

        return $data ? $this->toEntityFromArray($data) : null;
    }

    public function findBySlug(string $slug): ?TenantEntity
    {
        $cacheTag = "tenants:slugs"; 
        $cacheKey = "tenant:slug:{$slug}"; 

        $data = Cache::tags([$cacheTag])
            ->remember($cacheKey, self::TTL_LONG, function () use ($slug) {
                return Tenant::withoutGlobalScopes()
                    ->where('slug', $slug)
                    ->first()?->toArray();
            });

        return $data ? $this->toEntityFromArray($data) : null;
    }

    public function create(TenantEntity $entity): TenantEntity
    {
        $cacheTag = "tenants:slugs"; 
        $model = Tenant::create($this->toArray($entity));

        Cache::tags([$cacheTag])->flush();

        return $this->toEntityFromArray($model->toArray());
    }

    public function update(TenantEntity $entity): TenantEntity
    {
        $cacheTagId = "tenant:{$entity->id}"; 
        $cacheTagSlugs = "tenants:slugs"; 

        $model = Tenant::withoutGlobalScopes()->findOrFail($entity->id);
        $model->update($this->toArray($entity));

        Cache::tags([$cacheTagId, $cacheTagSlugs])->flush();

        return $this->toEntityFromArray($model->fresh()->toArray());
    }

    public function forceDelete(int $id): bool
    {
        $cacheTagId = "tenant:{$id}"; 
        $cacheTagSlug = "tenants:slugs"; 
        $result = (bool) Tenant::withoutGlobalScopes()
            ->findOrFail($id)
            ->forceDelete();

        Cache::tags([$cacheTagId, $cacheTagSlug])->flush();

        return $result;
    }

    public function attachUserWithTenant(int $tenantId, int $userId): void
    {
        $cacheTag = "user:{$userId}:tenants";
        Tenant::withoutGlobalScopes()
            ->findOrFail($tenantId)
            ->users()
            ->attach($userId);

        Cache::tags([$cacheTag])->flush();
    }

    public function detachUser(int $tenantId, int $userId): void
    {
        $cacheTag = "user:{$userId}:tenants";
        Tenant::withoutGlobalScopes()
            ->findOrFail($tenantId)
            ->users()
            ->detach($userId);

        Cache::tags([$cacheTag])->flush();
    }

    public function detachAllUsers(int $tenantId): void
    {
        $cacheTag = "tenant:{$tenantId}";

        $tenant  = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
        $userIds = $tenant->users()->pluck('users.id');

        $tenant->users()->detach();

        foreach ($userIds as $userId) {
            $cacheTagUserId = "user:{$userId}:tenants";
            Cache::tags([$cacheTagUserId])->flush();
        }

        Cache::tags([$cacheTag])->flush();
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

    private function toEntityFromArray(array $data): TenantEntity
    {
        return new TenantEntity(
            id:          $data['id'],
            name:        $data['name'],
            slug:        $data['slug'],
            isActive:    (bool) $data['is_active'],
            trialEndsAt: $data['trial_ends_at'] ? new \DateTime($data['trial_ends_at']) : null,
            settings:    $data['settings'] ?? [],
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
