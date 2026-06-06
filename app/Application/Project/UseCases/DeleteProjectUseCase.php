<?php

namespace App\Application\Project\UseCases;

use App\Application\Audit\AuditLoggerInterface;
use App\Domain\Project\Repositories\ProjectRepositoryInterface;

class DeleteProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly AuditLoggerInterface       $audit,
    ) {}

    public function execute(int $id, int $tenantId): bool
    {
        $project = $this->projectRepository->findById($id, $tenantId);

        if ($project === null) {
            throw new \DomainException("Project [{$id}] not found in this tenant.");
        }

        $snapshot = [
            'name'   => $project->name,
            'status' => $project->status,
        ];

        $result = $this->projectRepository->delete($id, $tenantId);

        $this->audit->log(
            action:     'project.deleted',
            entityId:   $id,
            entityType: 'Project',
            oldValues:  $snapshot,
        );

        return $result;
    }
}
