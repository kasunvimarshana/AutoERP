<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Vehicle\Contracts\VehicleAvailabilityBlockerInterface;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\VehicleUse;

final class VehicleUseAvailabilityBlocker implements VehicleAvailabilityBlockerInterface
{
    public const REASON = 'The vehicle has conflicting Rental use. Review its availability before continuing.';

    public function blockingReason(int $tenantId, ?int $organizationUnitId, int $vehicleId, string $startsAt, ?string $endsAt): ?string
    {
        return $this->conflicts($tenantId, $vehicleId, $startsAt, $endsAt) ? self::REASON : null;
    }

    public function conflicts(int $tenant, int $vehicle, string $start, ?string $end, ?int $exclude = null): bool
    {
        // A shared physical vehicle has one timeline across branches. Disclose no branch details.
        return VehicleUse::query()->forTenant($tenant)->where('vehicle_id', $vehicle)
            ->when($exclude !== null, fn (Builder $q) => $q->whereKeyNot($exclude))
            ->where(function (Builder $q) use ($start, $end): void {
                $q->where(function (Builder $planned) use ($start, $end): void {
                    $planned->where('status', VehicleUseStatus::Planned->value)->where(fn (Builder $period) => $period->whereNull('ends_at')->orWhere('ends_at', '>', $start))
                        ->when($end !== null, fn (Builder $range) => $range->where('starts_at', '<', $end));
                })->orWhere(function (Builder $custody) use ($end): void {
                    // Expected return does not release a vehicle still physically in customer custody.
                    $custody->where('status', VehicleUseStatus::InCustody->value)
                        ->when($end !== null, fn (Builder $range) => $range->where('handed_over_at', '<', $end));
                })->orWhere(function (Builder $returned) use ($start, $end): void {
                    $returned->where('status', VehicleUseStatus::Returned->value)->where('returned_at', '>', $start)
                        ->when($end !== null, fn (Builder $range) => $range->where('handed_over_at', '<', $end));
                });
            })->lockForUpdate()->first(['id']) !== null;
    }
}
