<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalRateUnit;

final readonly class RentalRateSnapshotData
{
    public function __construct(
        public string $baseRate,
        public RentalRateUnit $rateUnit,
        public string $allowedHours = '0.000000',
        public string $allowedKm = '0.000000',
        public string $extraHourRate = '0.000000',
        public string $extraKmRate = '0.000000',
        public string $overtimeRate = '0.000000',
        public string $doubleOvertimeRate = '0.000000',
        public string $nightShiftRate = '0.000000',
        public string $weekendRate = '0.000000',
        public string $holidayRate = '0.000000',
        public string $driverRate = '0.000000',
        public string $outstationRate = '0.000000',
        public string $dayOutRate = '0.000000',
        public string $nightOutRate = '0.000000',
        public string $fuelRate = '0.000000',
        public string $waitingHourRate = '0.000000',
        public ?int $taxProfileId = null,
        public ?int $currencyId = null,
    ) {}
}
