<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Project\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    private const int TTL_SHORT  = 300;
    private const int TTL_MEDIUM = 600;

    public function findAllByTenantId(int $tenantId, int $perPage = 10): LengthAwarePaginator
    {
        $page = request()->input('page', 1);
        $cacheTag = "tenant:{$tenantId}:projects"; 
        $cacheKey = "tenant:{$tenantId}:projects:page:{$page}:per:{$perPage}"; 

        $cached = Cache::tags([$cacheTag])
            ->remember($cacheKey, self::TTL_SHORT, function () use ($tenantId, $perPage) {
                $paginator = Project::where('tenant_id', $tenantId)->paginate($perPage);
                return [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'items'        => collect($paginator->items())->map(fn (Project $m) => $m->toArray())->all(),
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

    public function getAllByTenantId(int $tenantId): Collection
    {
        $cacheTag = "tenant:{$tenantId}:projects"; 
        $cacheKey = "tenant:{$tenantId}:projects:all";

        $rows = Cache::tags([$cacheTag])
            ->remember($cacheKey, self::TTL_SHORT, function () use ($tenantId) {
                return Project::where('tenant_id', $tenantId)
                    ->get()
                    ->map(fn (Project $m) => $m->toArray())
                    ->all();
            });

        return collect($rows)->map(fn (array $row) => $this->toEntityFromArray($row));
    }

    public function findById(int $id, int $tenantId): ?ProjectEntity
    {  
        $cacheTag = "tenant:{$tenantId}:projects"; 
        $cacheKey = "tenant:{$tenantId}:project:{$id}"; 

        $data = Cache::tags([$cacheTag])
            ->remember($cacheKey, self::TTL_MEDIUM, function () use ($id, $tenantId) {
                return Project::where('id', $id)->where('tenant_id', $tenantId)->first()?->toArray();
            });

        return $data ? $this->toEntityFromArray($data) : null;
    }

    public function create(ProjectEntity $entity): ProjectEntity
    {
        $model = Project::create($this->toArray($entity));
        $cacheTag = "tenant:{$entity->tenantId}:projects"; 

        Cache::tags([$cacheTag])->flush();

        return $this->toEntityFromArray($model->toArray());
    }

    public function update(ProjectEntity $entity): ProjectEntity
    {
        $model = Project::where('id', $entity->id)
            ->where('tenant_id', $entity->tenantId)
            ->firstOrFail();

        $model->update($this->toArray($entity));

        $cacheTag = "tenant:{$entity->tenantId}:projects"; 
        Cache::tags([$cacheTag])->flush();

        return $this->toEntityFromArray($model->fresh()->toArray());
    }

    public function delete(int $id, int $tenantId): bool
    {
        $result = (bool) Project::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail()
            ->delete();
            
        $cacheTag = "tenant:{$tenantId}:projects"; 
        Cache::tags([$cacheTag])->flush();

        return $result;
    }

    // ── Mapping helpers ───────────────────────────────────────────────────────

    private function toEntityFromArray(array $data): ProjectEntity
    {
        return new ProjectEntity(
            id:          $data['id'],
            tenantId:    $data['tenant_id'],
            ownerId:     $data['onwer_id'],
            name:        $data['name'],
            status:      $data['status'],
            description: $data['description'] ?? null,
        );
    }

    private function toArray(ProjectEntity $entity): array
    {
        return [
            'tenant_id'   => $entity->tenantId,
            'onwer_id'    => $entity->ownerId,
            'name'        => $entity->name,
            'status'      => $entity->status,
            'description' => $entity->description,
        ];
    }
}
