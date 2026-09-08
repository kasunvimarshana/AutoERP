<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use InvalidArgumentException;
use Modules\Vehicle\Contracts\VehicleAvailabilityBlockerInterface;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;

final class VehicleAvailabilityService
{
    /** @param iterable<VehicleAvailabilityBlockerInterface> $blockers */
    public function __construct(private readonly iterable $blockers) {}

    public function assertAvailable(
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        string $startsAt,
        ?string $endsAt,
        ?VehicleAvailabilityBlockerInterface $selfBlocker = null,
    ): Vehicle {
        $vehicleQuery = Vehicle::query()->where('tenant_id', $tenantId);
        $organizationUnitId === null
            ? $vehicleQuery->whereNull('organization_unit_id')
            : $vehicleQuery->where(function ($query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            });
        $vehicle = $vehicleQuery->lockForUpdate()->findOrFail($vehicleId);

        if ($vehicle->status !== VehicleStatus::Active) {
            throw new InvalidArgumentException('The selected vehicle is not operationally active.');
        }

        $this->assertUnblocked($tenantId, $organizationUnitId, $vehicleId, $startsAt, $endsAt, $selfBlocker);

        return $vehicle;
    }

    // The caller must hold the vehicle lock in the transaction that persists its operational admission.
    public function assertUnblocked(int $tenantId, ?int $organizationUnitId, int $vehicleId, string $startsAt, ?string $endsAt, ?VehicleAvailabilityBlockerInterface $selfBlocker = null): void
    {
        foreach ($this->blockers as $blocker) {
            if ($selfBlocker !== null && $blocker::class === $selfBlocker::class) {
                continue;
            }
            $reason = $blocker->blockingReason($tenantId, $organizationUnitId, $vehicleId, $startsAt, $endsAt);
            if ($reason !== null) {
                throw new InvalidArgumentException($reason);
            }
        }

    }
}
