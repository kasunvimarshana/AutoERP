<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services\Availability;

use Illuminate\Database\Eloquent\Builder;
use Modules\Vehicle\Contracts\VehicleAvailabilityBlockerInterface;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceAvailabilityBlocker implements VehicleAvailabilityBlockerInterface
{
    private const BLOCKING_STATUSES = [
        VehicleServiceJobStatus::Inspected->value,
        VehicleServiceJobStatus::InProgress->value,
    ];

    public function blockingReason(
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        string $startsAt,
        ?string $endsAt,
    ): ?string {
        $startsOn = substr($startsAt, 0, 10);
        $endsOn = substr($endsAt ?? $startsAt, 0, 10);
        $blocked = VehicleServiceJob::query()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->whereDate('job_date', '<=', $endsOn)
            ->where(function (Builder $query) use ($startsOn): void {
                $query->whereNull('expected_delivery_date')
                    ->orWhereDate('expected_delivery_date', '>=', $startsOn);
            })
            ->exists();

        return $blocked
            ? 'The selected vehicle is blocked by an active Vehicle Service job for the requested period.'
            : null;
    }
}
