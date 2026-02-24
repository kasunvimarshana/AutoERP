<?php

namespace Modules\HR\Domain\Entities;

class Employee
{
    public function __construct(
        public readonly string             $id,
        public readonly string             $tenant_id,
        public readonly ?string            $department_id,
        public readonly string             $first_name,
        public readonly string             $last_name,
        public readonly string             $email,
        public readonly ?string            $phone,
        public readonly string             $position,
        public readonly string             $salary,
        public readonly string             $hire_date,
        public readonly string             $status,
        public readonly ?\DateTimeImmutable $created_at,
        public readonly ?\DateTimeImmutable $updated_at,
    ) {}
}
