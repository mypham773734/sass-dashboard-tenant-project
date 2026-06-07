<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Entities\UserEntity;

interface UserRepositoryInterface
{
    public function findById(int $id): UserEntity;
    public function update(UserEntity $entity): UserEntity;
    public function updatePassword(int $id, string $hashedPassword): void;

    public function setMeta(int $id, string $key, string $value): void;

    public function getMeta(int $id, string $key); 
}
