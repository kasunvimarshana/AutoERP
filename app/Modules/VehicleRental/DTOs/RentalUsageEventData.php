<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalUsageEventType;

final readonly class RentalUsageEventData
{
    public function __construct(
        public RentalUsageEventType $eventType,
        public string $quantity,
        public ?string $remarks = null,
        public ?int $createdBy = null,
    ) {}
}
