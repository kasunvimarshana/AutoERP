<?php

declare(strict_types=1);

namespace Modules\Rental\Application\DTOs;

final class CreateRentalReservationDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $vehicleId,
        public readonly string $customerId,
        public readonly ?string $driverId,
        public readonly \DateTime $startAt,
        public readonly \DateTime $expectedReturnAt,
        public readonly string $billingUnit,
        public readonly string $baseRate,
        public readonly string $estimatedDistance,
        public readonly ?string $notes = null,
    ) {}
}
