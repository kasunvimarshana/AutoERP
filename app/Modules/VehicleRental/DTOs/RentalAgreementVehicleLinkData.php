<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

final readonly class RentalAgreementVehicleLinkData
{
    public function __construct(
        public int $inboundAgreementVehicleId,
        public int $outboundAgreementVehicleId,
        public string $effectiveFrom,
        public string $effectiveTo,
        public ?string $remarks = null,
        public ?int $createdBy = null,
    ) {}
}
