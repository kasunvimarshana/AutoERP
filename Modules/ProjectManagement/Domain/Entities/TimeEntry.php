<?php

namespace Modules\ProjectManagement\Domain\Entities;

class TimeEntry
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenant_id,
        public readonly string  $project_id,
        public readonly ?string $task_id,
        public readonly string  $user_id,
        public readonly string  $hours,
        public readonly ?string $description,
        public readonly string  $entry_date,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}
}
