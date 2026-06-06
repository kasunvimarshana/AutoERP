<?php

declare(strict_types=1);

namespace Modules\Vehicle\DTOs;

use Modules\Vehicle\Enums\VehicleStatus;

final readonly class VehicleResultData
{
    public function __construct(
        public int $vehicleId,
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $vehicleNumber,
        public ?string $code,
        public ?string $registrationNumber,
        public VehicleStatus $status,
        public ?int $customerId,
        public string $odometerReading,
    ) {}
}
