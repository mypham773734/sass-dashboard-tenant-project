<?php

namespace App\Application\User\UseCases;

use App\Application\Audit\AuditLoggerInterface;
use App\Application\User\DTOs\UpdateProfileDTO;
use App\Domain\User\Entities\UserEntity;
use App\Domain\User\Repositories\UserRepositoryInterface;

class UpdateProfileUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuditLoggerInterface    $audit,
    ) {}

    public function execute(UpdateProfileDTO $dto, int $userId): UserEntity
    {
        $existing = $this->userRepository->findById($userId);

        $oldValues = [
            'name'  => $existing->name,
            'email' => $existing->email,
            'phone' => $existing->phone,
        ];

        $updated = new UserEntity(
            id:        $existing->id,
            name:      $dto->name,
            email:     $dto->email,
            phone:     $dto->phone,
            avatar:    $dto->avatarPath ?? $existing->avatar,
            avatarUrl: null,
            tenants:   $existing->tenants,
        );

        $result = $this->userRepository->update($updated);

        $this->audit->log(
            action:     'profile.updated',
            entityId:   $userId,
            entityType: 'User',
            oldValues:  $oldValues,
            newValues:  ['name' => $dto->name, 'email' => $dto->email, 'phone' => $dto->phone],
        );

        return $result;
    }
}
