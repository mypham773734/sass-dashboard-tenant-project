<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Project\Entities\ProjectEntity;
use App\Domain\Project\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function findAllByTenantId(int $tenantId, int $perPage = 10): LengthAwarePaginator
    {
        return Project::where('tenant_id', $tenantId)
            ->paginate($perPage)
            ->through(fn (Project $model) => $this->toEntity($model));
    }

    public function findById(int $id, int $tenantId): ?ProjectEntity
    {
        $model = Project::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function create(ProjectEntity $entity): ProjectEntity
    {
        $model = Project::create($this->toArray($entity));

        return $this->toEntity($model);
    }

    public function update(ProjectEntity $entity): ProjectEntity
    {
        $model = Project::where('id', $entity->id)
            ->where('tenant_id', $entity->tenantId)
            ->firstOrFail();

        $model->update($this->toArray($entity));

        return $this->toEntity($model->fresh());
    }

    public function delete(int $id, int $tenantId): bool
    {
        return (bool) Project::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail()
            ->delete();
    }

    // ── Mapping helpers ───────────────────────────────────────────────────────

    private function toEntity(Project $model): ProjectEntity
    {
        return new ProjectEntity(
            id:          $model->id,
            tenantId:    $model->tenant_id,
            ownerId:     $model->onwer_id,  // note: DB column has typo
            name:        $model->name,
            status:      $model->status,
            description: $model->description,
        );
    }

    private function toArray(ProjectEntity $entity): array
    {
        return [
            'tenant_id'   => $entity->tenantId,
            'onwer_id'    => $entity->ownerId,  // note: DB column has typo
            'name'        => $entity->name,
            'status'      => $entity->status,
            'description' => $entity->description,
        ];
    }
}
