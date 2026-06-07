<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\User\Entities\UserEntity;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Models\UserMeta;

class EloquentUserRepository implements UserRepositoryInterface
{
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

    private function toEntity(User $model): UserEntity
    {
        $avatar  = $this->getMeta($model->id, 'avatar');
        $phone   = $this->getMeta($model->id, 'phone');

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
        );
    }
}
