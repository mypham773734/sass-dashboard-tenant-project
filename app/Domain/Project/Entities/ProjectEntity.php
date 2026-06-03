<?php

namespace App\Domain\Project\Entities;

class ProjectEntity
{
    public function __construct(
        public readonly ?int   $id,
        public readonly int    $tenantId,
        public readonly int    $ownerId,
        public readonly string $name,
        public readonly string $status,
        public readonly ?string $description = null,
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
