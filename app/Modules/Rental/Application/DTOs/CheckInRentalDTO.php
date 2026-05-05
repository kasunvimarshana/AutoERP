<?php

declare(strict_types=1);

namespace Modules\Rental\Application\DTOs;

final class CheckInRentalDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $agreementId,
        public readonly int $odometerIn,
        public readonly string $fuelLevelIn,
        public readonly ?string $dropoffLatitude = null,
        public readonly ?string $dropoffLongitude = null,
    ) {}
}
