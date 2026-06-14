<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalType;

final readonly class RentalReservationData
{
    public function __construct(
        public int $tenantId,
        public RentalAgreementDirection $direction,
        public RentalPartyType $partyType,
        public int $partyId,
        public RentalType $rentalType,
        public string $startAt,
        public string $expectedEndAt,
        public ?int $organizationUnitId = null,
        public ?string $reservationNumber = null,
        public ?int $vehicleId = null,
        public ?int $currencyId = null,
        public ?string $remarks = null,
        public ?int $createdBy = null,
    ) {}
}
