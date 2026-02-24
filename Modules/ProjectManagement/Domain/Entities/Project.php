<?php

namespace Modules\ProjectManagement\Domain\Entities;

class Project
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenant_id,
        public readonly string  $name,
        public readonly ?string $description,
        public readonly ?string $customer_id,
        public readonly string  $status,
        public readonly ?string $start_date,
        public readonly ?string $end_date,
        public readonly string  $budget,
        public readonly string  $spent,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}
}
