<?php

namespace App\Application\Tenant\UseCases;

use App\Application\Mail\Contracts\MailServiceInterface;
use App\Domain\Tenant\Repositories\TenantRepositoryInterface;

class DeleteTenantUseCase
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly MailServiceInterface      $mailService,
    ) {}

    public function execute(string $slug, int $userId, ?int $currentTenantId, string $actorName): bool
    {
        $tenant = $this->tenantRepository->findBySlug($slug);

        if ($tenant === null) {
            throw new \DomainException("Tenant with slug [{$slug}] not found.");
        }

        // Rule 1: requesting user must be a member of this tenant.
        if (! $this->tenantRepository->hasUser($tenant->id, $userId)) {
            throw new \DomainException('You do not have permission to delete this tenant.');
        }

        // Rule 2: cannot delete the currently active tenant.
        if ($currentTenantId !== null && $currentTenantId === $tenant->id) {
            throw new \DomainException('Cannot delete the tenant that is currently selected.');
        }

        // Notify admins before detaching users (while recipients can still be resolved).
        $this->mailService->dispatch('tenant_notification', $tenant->id, [
            'tenant_name' => $tenant->name,
            'event_title' => 'Workspace deleted',
            'event_type'  => 'security',
            'description' => "Workspace \"{$tenant->name}\" was permanently deleted by {$actorName}.",
            'actor_name'  => $actorName,
        ]);

        $this->tenantRepository->detachAllUsers($tenant->id);

        return $this->tenantRepository->forceDelete($tenant->id);
    }
}
