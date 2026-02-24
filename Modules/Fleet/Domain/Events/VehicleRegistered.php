<?php

namespace Modules\Fleet\Domain\Events;

class VehicleRegistered
{
    public function __construct(
        public readonly string $vehicleId,
        public readonly string $tenantId,
        public readonly string $plateNumber,
    ) {}
}
