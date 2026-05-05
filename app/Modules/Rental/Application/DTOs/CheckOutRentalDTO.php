<?php

declare(strict_types=1);

namespace Modules\Rental\Application\DTOs;

final class CheckOutRentalDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $agreementId,
        public readonly int $odometerOut,
        public readonly string $fuelLevelOut,
        public readonly ?string $pickupLatitude = null,
        public readonly ?string $pickupLongitude = null,
    ) {}
}
