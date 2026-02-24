<?php

namespace Modules\HR\Domain\Entities;

class Payslip
{
    public function __construct(
        public readonly string             $id,
        public readonly string             $tenant_id,
        public readonly string             $payroll_run_id,
        public readonly string             $employee_id,
        public readonly string             $gross_salary,
        public readonly string             $deductions,
        public readonly string             $net_salary,
        public readonly string             $status,
        public readonly ?\DateTimeImmutable $created_at,
        public readonly ?\DateTimeImmutable $updated_at,
    ) {}
}
