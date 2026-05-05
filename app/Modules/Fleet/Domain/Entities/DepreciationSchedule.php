<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\Entities;

class DepreciationSchedule
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $vehicleId,
        public readonly string $method,
        public readonly int $usefulLifeMonths,
        public readonly string $salvageValue,
        public readonly string $depreciableAmount,
        public readonly string $monthlyDepreciationAmount,
        public readonly string $accumulatedDepreciation,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly bool $isActive = true,
        public readonly ?int $id = null,
    ) {}
}
