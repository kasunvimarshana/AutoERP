<?php

namespace Modules\ProjectManagement\Domain\Entities;

class Milestone
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenant_id,
        public readonly string  $project_id,
        public readonly string  $name,
        public readonly string  $due_date,
        public readonly string  $status,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}
}
