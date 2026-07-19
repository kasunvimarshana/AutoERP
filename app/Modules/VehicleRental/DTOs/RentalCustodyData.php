<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalCustodyEventType;

final readonly class RentalCustodyData
{
    public function __construct(
        public RentalCustodyEventType $eventType,
        public string $eventAt,
        public string $odometer,
        public ?string $fuelLevel,
        public ?string $conditionNotes,
        public ?string $damageNotes,
        public ?int $actorId,
    ) {}
}
