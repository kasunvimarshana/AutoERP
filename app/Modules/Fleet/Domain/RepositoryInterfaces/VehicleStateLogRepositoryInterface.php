<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\RepositoryInterfaces;

interface VehicleStateLogRepositoryInterface
{
    public function append(
        int $tenantId,
        int $vehicleId,
        string $fromState,
        string $toState,
        string $reason,
        ?string $referenceType,
        ?int $referenceId,
        ?int $triggeredBy,
    ): void;
}
