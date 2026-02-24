<?php

namespace Modules\HR\Domain\Entities;

class SalaryStructureAssignment
{
    public function __construct(
        public readonly string             $id,
        public readonly string             $tenant_id,
        public readonly string             $employee_id,
        public readonly string             $structure_id,
        public readonly string             $base_amount,
        public readonly string             $effective_from,
        public readonly ?\DateTimeImmutable $created_at,
        public readonly ?\DateTimeImmutable $updated_at,
    ) {}
}
