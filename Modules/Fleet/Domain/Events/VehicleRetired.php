<?php

namespace Modules\Fleet\Domain\Events;

class VehicleRetired
{
    public function __construct(
        public readonly string $vehicleId,
        public readonly string $tenantId,
    ) {}
}
