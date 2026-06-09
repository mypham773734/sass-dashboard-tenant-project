<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Mail\Contracts\MailServiceInterface;
use App\Domain\Tenant\Entities\TenantEntity;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\DTOs\Tenants\CreateTenantDTO;

class CreateTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface   $userRepository,
        private readonly MailServiceInterface      $mailService,
    ) {}

    public function execute(CreateTenantDTO $dto, int $creatorUserId): TenantEntity
    {
        $entity = new TenantEntity(
            id:          null,
            name:        $dto->name,
            slug:        $dto->slug,
            isActive:    $dto->is_active,
            trialEndsAt: $dto->trial_ends_at,
        );

        $created = $this->tenantRepository->create($entity);

        // Business rule: the creator is always the first admin of the tenant.
        $this->tenantRepository->attachUser($created->id, $creatorUserId, 'admin');

        $creator = $this->userRepository->findById($creatorUserId);

        $this->mailService->dispatch('tenant_notification', $created->id, [
            'tenant_name' => $created->name,
            'event_title' => 'New workspace created',
            'event_type'  => 'settings_changed',
            'description' => "Workspace \"{$created->name}\" was created by {$creator->name}.",
            'actor_name'  => $creator->name,
        ]);

        return $created;
    }
}
