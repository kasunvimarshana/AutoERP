<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Entities;

use DateTimeImmutable;
use Modules\Reservation\Domain\ValueObjects\ReservationStatus;

class Reservation
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $orgUnitId,
        public readonly int $rowVersion,
        public readonly string $reservationNumber,
        public readonly string $vehicleId,
        public readonly string $customerId,
        public readonly DateTimeImmutable $reservedFrom,
        public readonly DateTimeImmutable $reservedTo,
        public readonly ReservationStatus $status,
        public readonly string $estimatedAmount,
        public readonly string $currency,
        public readonly ?string $notes,
        public readonly ?array $metadata,
        public readonly bool $isActive,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }
}
