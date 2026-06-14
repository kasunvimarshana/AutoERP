<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

final readonly class RentalUsageLogData
{
    public function __construct(
        public int $agreementVehicleId,
        public string $usageDate,
        public string $startOdometer,
        public string $endOdometer,
        public ?int $driverId = null,
        public ?string $startTime = null,
        public ?string $endTime = null,
        public ?string $comparativeKm = null,
        public ?string $tripFrom = null,
        public ?string $tripTo = null,
        public ?string $tripPurpose = null,
        public ?string $odometerVarianceReason = null,
        public ?string $remarks = null,
        public ?int $createdBy = null,
    ) {}
}
