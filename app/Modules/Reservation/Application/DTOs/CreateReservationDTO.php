<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\DTOs;

use DateTimeImmutable;

class CreateReservationDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $orgUnitId,
        public readonly string $reservationNumber,
        public readonly string $vehicleId,
        public readonly string $customerId,
        public readonly DateTimeImmutable $reservedFrom,
        public readonly DateTimeImmutable $reservedTo,
        public readonly string $estimatedAmount,
        public readonly string $currency,
        public readonly ?string $notes,
        public readonly ?array $metadata,
    ) {
    }
}
