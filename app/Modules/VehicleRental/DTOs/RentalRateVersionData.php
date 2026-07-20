<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

final readonly class RentalRateVersionData
{
    /** @param list<RentalRateLineData> $rates */
    public function __construct(
        public string $effectiveFrom,
        public array $rates,
        public ?int $actorId,
    ) {}
}
