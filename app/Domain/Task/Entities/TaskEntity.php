<?php

namespace App\Domain\Task\Entities;

class TaskEntity
{
    public function __construct(
        public readonly ?int       $id,
        public readonly int        $tenantId,
        public readonly int        $projectId,
        public readonly int        $createdBy,
        public readonly ?int       $assigneeId,
        public readonly string     $title,
        public readonly ?string    $description,
        public readonly string     $status,
        public readonly string     $priority,
        public readonly int        $order,
        public readonly ?\DateTime $dueDate,
        public readonly ?\DateTime $completedAt,
        public readonly ?string    $tenantTitle = null,
        public readonly ?string    $projectTitle = null,
    ) {}
}
