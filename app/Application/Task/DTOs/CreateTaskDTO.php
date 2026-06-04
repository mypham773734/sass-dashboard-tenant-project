<?php

namespace App\Application\Task\DTOs;

class CreateTaskDTO
{
    public function __construct(
        public readonly int     $projectId,
        public readonly string  $title,
        public readonly ?string $description,
        public readonly string  $status,
        public readonly string  $priority,
        public readonly ?string $dueDate,
        public readonly ?int    $assigneeId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            projectId:   (int) $data['project_id'],
            title:       $data['title'],
            description: $data['description'] ?? null,
            status:      $data['status'],
            priority:    $data['priority'],
            dueDate:     $data['due_date'] ?? null,
            assigneeId:  isset($data['assignee_id']) ? (int) $data['assignee_id'] : null,
        );
    }
}
