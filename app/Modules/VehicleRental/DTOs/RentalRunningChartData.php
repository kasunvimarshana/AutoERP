<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalAcMode;

final readonly class RentalRunningChartData
{
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public int $assignmentId,
        public string $operationalDate,
        public string $startsAt,
        public string $endsAt,
        public string $startOdometer,
        public string $endOdometer,
        public string $garageKm,
        public string $normalOvertimeHours,
        public string $doubleOvertimeHours,
        public string $tripleOvertimeHours,
        public int $nightOutCount,
        public ?RentalAcMode $acMode,
        public ?string $tripOrigin,
        public ?string $tripDestination,
        public ?string $purpose,
        public ?string $odometerVarianceReason,
        public ?string $remarks,
        public ?int $actorId,
    ) {}
}
