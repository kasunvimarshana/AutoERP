<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services\Availability;

use Illuminate\Database\Eloquent\Builder;
use Modules\Vehicle\Contracts\VehicleAvailabilityBlockerInterface;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceAvailabilityBlocker implements VehicleAvailabilityBlockerInterface
{
    private const DATE_PREFIX_LENGTH = 10;

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
        $startsOn = substr($startsAt, 0, self::DATE_PREFIX_LENGTH);
        $endsOn = $endsAt === null ? null : substr($endsAt, 0, self::DATE_PREFIX_LENGTH);
        $blocked = VehicleServiceJob::query()
            // Availability belongs to the physical vehicle, including jobs in other branches.
            // Return only the generic blocking reason, never another branch's job details.
            ->forTenant($tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->when($endsOn !== null, fn (Builder $query) => $query->whereDate('job_date', '<=', $endsOn))
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
