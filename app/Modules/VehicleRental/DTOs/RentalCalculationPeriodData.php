<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

final readonly class RentalCalculationPeriodData
{
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $periodStart,
        public string $periodEnd,
        public ?int $actorId,
    ) {}
}
