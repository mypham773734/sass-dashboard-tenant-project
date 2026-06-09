<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Mail\Contracts\MailServiceInterface;
use App\Application\Tenant\DTOs\UpdateTenantDTO;
use App\Domain\Tenant\Entities\TenantEntity;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;

class UpdateTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly MailServiceInterface      $mailService,
    ) {}

    public function execute(string $slug, UpdateTenantDTO $dto, string $actorName): TenantEntity
    {
        $existing = $this->tenantRepository->findBySlug($slug);

        if ($existing === null) {
            throw new \DomainException("Tenant with slug [{$slug}] not found.");
        }

        $updated = new TenantEntity(
            id:          $existing->id,
            name:        $dto->name,
            slug:        $existing->slug,
            isActive:    $dto->isActive,
            trialEndsAt: $dto->trialEndsAt,
            settings:    $dto->settings ?? $existing->settings,
        );

        $result = $this->tenantRepository->update($updated);

        $this->mailService->dispatch('tenant_notification', $result->id, [
            'tenant_name' => $result->name,
            'event_title' => 'Workspace settings updated',
            'event_type'  => 'settings_changed',
            'description' => "Workspace settings for \"{$result->name}\" were updated by {$actorName}.",
            'actor_name'  => $actorName,
        ]);

        return $result;
    }
}
