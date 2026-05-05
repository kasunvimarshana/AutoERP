<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\Events;

class VehicleStateChanged
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $vehicleId,
        public readonly string $registrationNumber,
        public readonly string $fromState,
        public readonly string $toState,
        public readonly ?string $referenceType,
        public readonly ?int $referenceId,
        public readonly ?int $triggeredBy,
    ) {}
}
