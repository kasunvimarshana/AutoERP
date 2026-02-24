<?php

namespace Modules\ProjectManagement\Domain\Entities;

class Task
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenant_id,
        public readonly string  $project_id,
        public readonly string  $title,
        public readonly ?string $description,
        public readonly ?string $assigned_to,
        public readonly string  $status,
        public readonly string  $priority,
        public readonly ?string $due_date,
        public readonly string  $estimated_hours,
        public readonly string  $actual_hours,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}
}
