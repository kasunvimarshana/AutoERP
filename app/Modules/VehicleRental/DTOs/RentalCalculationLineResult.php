<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

final readonly class RentalCalculationLineResult
{
    public function __construct(
        public int $rateLineId,
        public RentalRateCode $rateCode,
        public RentalRateUnit $unit,
        public string $quantity,
        public string $unitRate,
        public string $lineTotal,
        public bool $isTaxable,
        public ?string $description,
    ) {}
}
