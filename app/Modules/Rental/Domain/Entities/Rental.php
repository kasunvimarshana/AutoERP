<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

use DateTimeImmutable;
use Modules\Rental\Domain\ValueObjects\RentalStatus;
use Modules\Rental\Domain\ValueObjects\RentalType;

class Rental
{
    public function __construct(
        public readonly int|null $id,
        public readonly int $tenantId,
        public readonly int|null $orgUnitId,
        public readonly int $customerId,
        public readonly int $vehicleId,
        public readonly int|null $driverId,
        public readonly string $rentalNumber,
        public readonly RentalType $rentalType,
        public readonly RentalStatus $status,
        public readonly string|null $pickupLocation,
        public readonly string|null $returnLocation,
        public readonly DateTimeImmutable $scheduledStartAt,
        public readonly DateTimeImmutable $scheduledEndAt,
        public readonly DateTimeImmutable|null $actualStartAt,
        public readonly DateTimeImmutable|null $actualEndAt,
        public readonly string|null $startOdometer,
        public readonly string|null $endOdometer,
        public readonly string $ratePerDay,
        public readonly string $estimatedDays,
        public readonly string|null $actualDays,
        public readonly string $subtotal,
        public readonly string $discountAmount,
        public readonly string $taxAmount,
        public readonly string $totalAmount,
        public readonly string $depositAmount,
        public readonly string|null $notes,
        public readonly DateTimeImmutable|null $cancelledAt,
        public readonly string|null $cancellationReason,
        public readonly array|null $metadata,
        public readonly bool $isActive,
        public readonly int $rowVersion,
    ) {}
}
