<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Entities;

readonly class AnalyticsSnapshot
{
    public function __construct(
        public ?int $id,
        public int $tenantId,
        public ?int $orgUnitId,
        public string $summaryDate,
        public int $totalRentals,
        public int $completedRentals,
        public int $activeRentals,
        public string $totalRevenue,
        public string $totalServiceCost,
        public string $netRevenue,
        public ?array $metadata,
        public bool $isActive,
        public int $rowVersion,
    ) {}
}
