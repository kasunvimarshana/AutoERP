<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\DTOs;

final class ChangeVehicleStateDTO
{
    public function __construct(
        public readonly int $vehicleId,
        public readonly string $toState,
        public readonly string $reason,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
        public readonly ?int $triggeredBy = null,
    ) {}
}
