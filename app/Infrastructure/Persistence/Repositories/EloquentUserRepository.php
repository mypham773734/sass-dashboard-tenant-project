<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\User\Entities\UserEntity;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator; 
use App\Application\User\DTOs\CreateUserDTO; 
use App\Domain\User\Enums\RoleEnum; 

class EloquentUserRepository implements UserRepositoryInterface
{
    
    private const int TTL_SHORT  = 300;
    private const int TTL_MEDIUM = 600;

    public function findById(int $id): UserEntity
    {
        $user = User::with('tenants')->findOrFail($id);
        return $this->toEntity($user);
    }

    public function update(UserEntity $entity): UserEntity
    {
        $user = User::findOrFail($entity->id);
        $user->update([
            'name'  => $entity->name,
            'email' => $entity->email,
        ]);

        $this->setMeta($entity->id, 'phone',  $entity->phone);
        $this->setMeta($entity->id, 'avatar', $entity->avatar);

        return $this->toEntity($user->fresh('tenants'));
    }

    public function updatePassword(int $id, string $hashedPassword): void
    {
        User::where('id', $id)->update([
            'password'       => $hashedPassword,
            'remember_token' => null,
        ]);
    }

    public function getMeta(int $userId, string $key): ?string
    {
        return UserMeta::where('user_id', $userId)
            ->where('key', $key)
            ->value('value');
    }

    public function setMeta(int $userId, string $key, ?string $value): void
    {
        UserMeta::updateOrCreate(
            ['user_id' => $userId, 'key'   => $key],
            ['value'   => $value],
        );
    }

    public function getUsersByTenant(int $tenantID){
        // return 
    }

    public function getAllSystemUser(int $perPage = 10)
    {
        $page = request()->input('page', 1);
        $perPage = request()->input('perPage', $perPage);

        $cacheTag = "user";
        $cacheKey = "user:page:{$page}:per:{$perPage}";

        $cached = Cache::tags([$cacheTag])
            ->remember($cacheKey, self::TTL_MEDIUM, function () use ($perPage) {
                $paginator = User::orderBy('created_at', 'desc')
                    ->paginate($perPage);
                return [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'items' => collect($paginator->items())->map(fn(User $user) => array_merge([
                        $user->toArray(),
                    ]))->all(),
                ];
            });
        $items = collect($cached['items'])->map(fn (array $row) => $this->toEntityFromArray($row));

        return new LengthAwarePaginator(
            $items,
            $cached['total'],
            $cached['per_page'],
            $cached['current_page'],
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function getSystemAdmin(){
        $systemAdminRole = RoleEnum::SYSTEM_ADMIN->value; 
        return User::whereHas('roles', function($q) use ($systemAdminRole){
            return $q->where('name', $systemAdminRole); 
        })->first(); 
    }

    public function create(CreateUserDTO $dto):UserEntity{
        $model = User::create($dto->toArray()); 
        return $this->toEntityFromArray($model->toArray());
    }

    // Find User has role admin
    public function findAdminsByTenant(int $tenantId): array
    {
        $adminRole = RoleEnum::ADMIN->value; 
        $ownerRole = RoleEnum::OWNER->value; 

        return User::whereHas('roles', function($q) use ($tenantId, $ownerRole, $adminRole) {
            $q->where('tenant_id', $tenantId)->whereIn('name',[$adminRole, $ownerRole]); 
        })->pluck('id')->toArray(); 
    }

    private function toEntity(User $model): UserEntity
    {
        $tenantId = tenantContext()->getId(); 
        $avatar  = $this->getMeta($model->id, 'avatar');
        $phone   = $this->getMeta($model->id, 'phone');
        
        $role = $model->rolesForTenant($tenantId); 

        $tenants = $model->tenants->map(fn($t) => [
            'id'   => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
            'role' => $model->rolesForTenant($t->id)->first()?->name ?? 'member',
        ])->toArray();

        return new UserEntity(
            id:        $model->id,
            name:      $model->name,
            email:     $model->email,
            phone:     $phone,
            avatar:    $avatar,
            avatarUrl: $avatar ? asset('storage/' . $avatar) : null,
            tenants:   $tenants,
            role: $role
        );
    }

    private function toEntityFromArray(array $data){
        $avatar  = $this->getMeta($data['id'], 'avatar');
        $phone   = $this->getMeta($data['id'], 'phone');
        
        // $role = $model->rolesForTenant($tenantId); 

        // $tenants = $model->tenants->map(fn($t) => [
        //     'id'   => $t->id,
        //     'name' => $t->name,
        //     'slug' => $t->slug,
        //     'role' => $model->rolesForTenant($t->id)->first()?->name ?? 'member',
        // ])->toArray();

        return new UserEntity(
            id:        $data['id'],
            name:      $data['name'],
            email:     $data['email'],
            phone:     $phone,
            avatar:    $avatar,
            avatarUrl: $avatar ? asset('storage/' . $avatar) : null,
            tenants:   [],
            role: '', 
        );
    }
}
