<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

final readonly class RentalRateLineData
{
    public function __construct(
        public RentalRateCode $code,
        public RentalRateUnit $unit,
        public string $rate,
        public bool $isTaxable,
        public ?string $description = null,
    ) {}
}
