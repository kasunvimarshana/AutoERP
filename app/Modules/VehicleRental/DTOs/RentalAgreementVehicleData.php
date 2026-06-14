<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalPartyType;

final readonly class RentalAgreementVehicleData
{
    public function __construct(
        public int $vehicleId,
        public string $allocatedFrom,
        public string $startOdometer,
        public ?string $allocatedTo = null,
        public ?RentalPartyType $ownerPartyType = null,
        public ?int $ownerPartyId = null,
        public ?string $remarks = null,
        public ?int $createdBy = null,
        public ?int $replacesAgreementVehicleId = null,
    ) {}
}
