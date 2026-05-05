<?php

declare(strict_types=1);

namespace Modules\Rental\Application\DTOs;

class UpdateRentalDTO
{
    public function __construct(
        public readonly int|null $driverId,
        public readonly string|null $pickupLocation,
        public readonly string|null $returnLocation,
        public readonly string|null $scheduledStartAt,
        public readonly string|null $scheduledEndAt,
        public readonly string|null $ratePerDay,
        public readonly string|null $estimatedDays,
        public readonly string|null $depositAmount,
        public readonly string|null $notes,
        public readonly array|null $metadata,
    ) {}
}
