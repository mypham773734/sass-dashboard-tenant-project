<?php

namespace App\Application\User\UseCases;

use App\Application\Audit\AuditLoggerInterface;
use App\Application\User\DTOs\ChangePasswordDTO;
use App\Domain\User\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class ChangePasswordUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuditLoggerInterface    $audit,
    ) {}

    public function execute(ChangePasswordDTO $dto, int $userId): void
    {
        $user = \App\Models\User::findOrFail($userId);

        if (! Hash::check($dto->currentPassword, $user->password)) {
            throw new \DomainException('Current password is incorrect.');
        }

        $this->userRepository->updatePassword($userId, Hash::make($dto->newPassword));

        $this->audit->log(
            action:     'profile.password_changed',
            entityId:   $userId,
            entityType: 'User',
        );
    }
}
