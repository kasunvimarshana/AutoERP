<?php

namespace Modules\Fleet\Domain\Events;

class MaintenanceLogged
{
    public function __construct(
        public readonly string $maintenanceId,
        public readonly string $tenantId,
        public readonly string $vehicleId,
        public readonly string $maintenanceType,
    ) {}
}
