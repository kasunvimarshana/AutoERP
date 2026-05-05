<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\Events;

class VehicleServiceStatusChanged
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $vehicleId,
        public readonly string $previousStatus,
        public readonly string $currentStatus,
        public readonly ?int $jobCardId = null,
    ) {}
}
