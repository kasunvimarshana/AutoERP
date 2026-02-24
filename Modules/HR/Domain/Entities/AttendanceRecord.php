<?php

namespace Modules\HR\Domain\Entities;

class AttendanceRecord
{
    public function __construct(
        public readonly string              $id,
        public readonly string              $tenant_id,
        public readonly string              $employee_id,
        public readonly string              $work_date,
        public readonly ?string             $check_in,
        public readonly ?string             $check_out,
        public readonly ?string             $duration_hours,
        public readonly string              $status,
        public readonly ?string             $notes,
        public readonly ?\DateTimeImmutable $created_at,
        public readonly ?\DateTimeImmutable $updated_at,
    ) {}
}
