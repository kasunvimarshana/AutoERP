<?php

namespace Modules\HR\Domain\Entities;

class PayrollRun
{
    public function __construct(
        public readonly string             $id,
        public readonly string             $tenant_id,
        public readonly string             $period_start,
        public readonly string             $period_end,
        public readonly string             $status,
        public readonly string             $total_gross,
        public readonly string             $total_net,
        public readonly ?\DateTimeImmutable $created_at,
        public readonly ?\DateTimeImmutable $updated_at,
    ) {}
}
