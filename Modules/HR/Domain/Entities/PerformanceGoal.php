<?php

namespace Modules\HR\Domain\Entities;

class PerformanceGoal
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenantId,
        public readonly string  $employeeId,
        public readonly string  $title,
        public readonly ?string $description,
        public readonly string  $period,
        public readonly ?int    $year,
        public readonly ?string $dueDate,
        public readonly string  $status,
        public readonly ?string $completedAt,
    ) {}
}
