<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

final readonly class RentalCalculationResult
{
    /** @param list<RentalCalculationLineResult> $lines */
    public function __construct(
        public int $operatingDays,
        public ?string $commercialKm,
        public string $includedKm,
        public ?string $excessKm,
        public string $subtotalAmount,
        public array $lines,
    ) {}
}
