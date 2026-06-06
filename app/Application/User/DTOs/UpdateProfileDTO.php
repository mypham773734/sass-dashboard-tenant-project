<?php

namespace App\Application\User\DTOs;

class UpdateProfileDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $email,
        public readonly ?string $phone,
        public readonly ?string $avatarPath,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:       $data['name'],
            email:      $data['email'],
            phone:      $data['phone'] ?? null,
            avatarPath: $data['avatar_path'] ?? null,
        );
    }
}
