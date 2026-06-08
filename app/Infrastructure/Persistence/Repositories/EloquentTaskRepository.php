<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Task\Entities\TaskEntity;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Support\Facades\Cache;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    private const int TTL_SHORT  = 300;
    private const int TTL_MEDIUM = 600;

    public function findAllByTenantId(int $tenantId, int $perPage = 10): LengthAwarePaginator
    {
        $page = request()->input('page', 1);
        $cacheTag = "tenant:{$tenantId}:tasks"; 
        $cacheKey = "tenant:{$tenantId}:tasks:page:{$page}:per:{$perPage}"; 
        $cached = Cache::tags([$cacheTag])
            ->remember($cacheKey, self::TTL_SHORT, function () use ($tenantId, $perPage) {
                $paginator = Task::with(['tenant:id,name', 'project:id,name'])
                    ->where('tenant_id', $tenantId)
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
                return [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'items'        => collect($paginator->items())->map(fn (Task $m) => array_merge(
                        $m->toArray(),
                        ['tenant_title' => $m->tenant?->name, 'project_title' => $m->project?->name],
                    ))->all(),
                ];
            });

        $items = collect($cached['items'])->map(fn (array $row) => $this->toEntityFromArray($row));

        return new ConcretePaginator(
            $items,
            $cached['total'],
            $cached['per_page'],
            $cached['current_page'],
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function findById(int $id, int $tenantId): ?TaskEntity
    {
        $cacheTag = "tenant:{$tenantId}:tasks"; 
        $cacheKey = "tenant:{$tenantId}:task:{$id}"; 

        $data = Cache::tags([$cacheTag])
            ->remember($cacheKey, self::TTL_MEDIUM, function () use ($id, $tenantId) {
                return Task::where('id', $id)->where('tenant_id', $tenantId)->first()?->toArray();
            });

        return $data ? $this->toEntityFromArray($data) : null;
    }

    public function create(TaskEntity $entity): TaskEntity
    {
        $cacheTag = "tenant:{$entity->tenantId}:tasks"; 

        $model = Task::create($this->toArray($entity));
        Cache::tags([$cacheTag])->flush();

        return $this->toEntityFromArray($model->toArray());
    }

    public function update(TaskEntity $entity): TaskEntity
    {
        $cacheTag = "tenant:{$entity->tenantId}:tasks"; 

        $model = Task::where('id', $entity->id)
            ->where('tenant_id', $entity->tenantId)
            ->firstOrFail();

        $model->update($this->toArray($entity));

        Cache::tags([$cacheTag])->flush();

        return $this->toEntityFromArray($model->fresh()->toArray());
    }

    public function delete(int $id, int $tenantId): bool
    {
        $cacheTag = "tenant:{$tenantId}:tasks"; 
        $result = (bool) Task::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail()
            ->delete();

        Cache::tags([$cacheTag])->flush();

        return $result;
    }

    // ── Mapping helpers ───────────────────────────────────────────────────────

    private function toEntityFromArray(array $data): TaskEntity
    {
        return new TaskEntity(
            id:           $data['id'],
            tenantId:     $data['tenant_id'],
            projectId:    $data['project_id'],
            createdBy:    $data['created_by'],
            assigneeId:   $data['assignee_id'],
            title:        $data['title'],
            description:  $data['description'] ?? null,
            status:       $data['status'],
            priority:     $data['priority'],
            order:        $data['order'],
            dueDate:      $data['due_date'] ? new \DateTime($data['due_date']) : null,
            completedAt:  $data['completed_at'] ? new \DateTime($data['completed_at']) : null,
            tenantTitle:  $data['tenant_title'] ?? null,
            projectTitle: $data['project_title'] ?? null,
        );
    }

    private function toArray(TaskEntity $entity): array
    {
        return [
            'tenant_id'    => $entity->tenantId,
            'project_id'   => $entity->projectId,
            'created_by'   => $entity->createdBy,
            'assignee_id'  => $entity->assigneeId,
            'title'        => $entity->title,
            'description'  => $entity->description,
            'status'       => $entity->status,
            'priority'     => $entity->priority,
            'order'        => $entity->order,
            'due_date'     => $entity->dueDate?->format('Y-m-d'),
            'completed_at' => $entity->completedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
