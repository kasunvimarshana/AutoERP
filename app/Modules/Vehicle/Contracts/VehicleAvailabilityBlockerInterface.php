<?php

declare(strict_types=1);

namespace Modules\Vehicle\Contracts;

interface VehicleAvailabilityBlockerInterface
{
    public const TAG = 'vehicle.availability_blocker';

    public function blockingReason(
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        string $startsAt,
        ?string $endsAt,
    ): ?string;
}
