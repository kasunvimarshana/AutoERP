<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalType;

final readonly class RentalAgreementData
{
    /**
     * @param array<string, mixed>|null $termsSnapshot
     */
    public function __construct(
        public int $tenantId,
        public RentalAgreementDirection $direction,
        public RentalPartyType $partyType,
        public int $partyId,
        public RentalType $rentalType,
        public RentalBillingCycle $billingCycle,
        public string $agreementDate,
        public string $startAt,
        public string $expectedEndAt,
        public RentalRateSnapshotData $rateSnapshot,
        public ?int $organizationUnitId = null,
        public ?string $agreementNumber = null,
        public ?int $reservationId = null,
        public ?int $currencyId = null,
        public ?array $termsSnapshot = null,
        public ?string $remarks = null,
        public ?int $createdBy = null,
    ) {}
}
