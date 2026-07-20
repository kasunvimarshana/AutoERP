<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalBillingBasis;

final readonly class RentalAgreementData
{
    /** @param list<RentalRateLineData> $rates */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public RentalAgreementKind $kind,
        public ?int $customerId,
        public ?int $supplierId,
        public ?string $agreementNumber,
        public ?string $executedAt,
        public string $startsOn,
        public ?string $endsOn,
        public RentalBillingBasis $billingBasis,
        public int $currencyId,
        public ?int $taxGroupId,
        public string $includedKm,
        public bool $depositRequired,
        public string $depositAmount,
        public int $paymentTermsDays,
        public ?string $terms,
        public ?string $notes,
        public array $rates,
        public ?int $actorId,
    ) {}
}
