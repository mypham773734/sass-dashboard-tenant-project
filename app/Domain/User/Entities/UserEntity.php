<?php

namespace App\Domain\User\Entities;

class UserEntity
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly string  $email,
        public readonly ?string $phone,
        public readonly ?string $avatar,
        public readonly ?string $avatarUrl,
        public readonly array   $tenants,
    ) {}
}
