<?php

declare(strict_types=1);

namespace Modules\Rental\Application\DTOs;

use Modules\Rental\Domain\ValueObjects\RentalType;

class CreateRentalDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int|null $orgUnitId,
        public readonly int $customerId,
        public readonly int $vehicleId,
        public readonly int|null $driverId,
        public readonly string $rentalNumber,
        public readonly RentalType $rentalType,
        public readonly string $scheduledStartAt,
        public readonly string $scheduledEndAt,
        public readonly string|null $pickupLocation,
        public readonly string|null $returnLocation,
        public readonly string $ratePerDay,
        public readonly string $estimatedDays,
        public readonly string $depositAmount,
        public readonly string|null $notes,
        public readonly array|null $metadata,
    ) {}
}
