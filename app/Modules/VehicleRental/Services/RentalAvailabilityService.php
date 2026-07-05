<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleRental\Models\RentalVehicleAllocation;

final class RentalAvailabilityService
{
    /** @var list<string> */
    private const BLOCKING_ALLOCATION_STATUSES = [
        RentalAllocationStatus::Planned->value,
        RentalAllocationStatus::Active->value,
    ];

    /** @var list<string> */
    private const BLOCKING_RESERVATION_STATUSES = [
        RentalReservationStatus::Pending->value,
        RentalReservationStatus::Confirmed->value,
    ];

    public function assertVehicle(
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        string $startAt,
        string $endAt,
        ?int $excludeAllocationId = null,
        ?int $excludeReservationId = null,
        ?int $allowedSourceAllocationId = null,
    ): Vehicle {
        if ($endAt <= $startAt) {
            throw new InvalidArgumentException('Vehicle availability end must be after its start.');
        }

        $vehicle = Vehicle::query()
            ->where('tenant_id', $tenantId)
            ->when(
                $organizationUnitId === null,
                fn (Builder $query) => $query->whereNull('organization_unit_id'),
                fn (Builder $query) => $query->where('organization_unit_id', $organizationUnitId),
            )
            ->whereNotIn('status', [
                VehicleStatus::Inactive->value,
                VehicleStatus::Sold->value,
                VehicleStatus::Blocked->value,
                VehicleStatus::Scrapped->value,
            ])
            ->lockForUpdate()
            ->findOrFail($vehicleId);

        $allocationConflict = RentalVehicleAllocation::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', self::BLOCKING_ALLOCATION_STATUSES)
            ->when($excludeAllocationId !== null, fn (Builder $query) => $query->whereKeyNot($excludeAllocationId))
            ->when($allowedSourceAllocationId !== null, fn (Builder $query) => $query->whereKeyNot($allowedSourceAllocationId))
            ->where('allocated_from', '<', $endAt)
            ->where(fn (Builder $query) => $query->whereNull('allocated_to')->orWhere('allocated_to', '>', $startAt))
            ->exists();

        if ($allocationConflict) {
            throw new InvalidArgumentException('Vehicle is already allocated during the requested period.');
        }

        $reservationConflict = RentalReservation::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('requested_vehicle_id', $vehicleId)
            ->whereIn('status', self::BLOCKING_RESERVATION_STATUSES)
            ->when($excludeReservationId !== null, fn (Builder $query) => $query->whereKeyNot($excludeReservationId))
            ->where('requested_start_at', '<', $endAt)
            ->where('requested_end_at', '>', $startAt)
            ->exists();

        if ($reservationConflict) {
            throw new InvalidArgumentException('Vehicle is reserved during the requested period.');
        }

        return $vehicle;
    }

    public function queryAvailable(
        int $tenantId,
        ?int $organizationUnitId,
        string $startAt,
        string $endAt,
        ?int $categoryId = null,
        ?string $search = null,
    ): Builder {
        if ($endAt <= $startAt) {
            throw new InvalidArgumentException('Vehicle availability end must be after its start.');
        }

        return Vehicle::query()
            ->where('tenant_id', $tenantId)
            ->when(
                $organizationUnitId === null,
                fn (Builder $query) => $query->whereNull('organization_unit_id'),
                fn (Builder $query) => $query->where('organization_unit_id', $organizationUnitId),
            )
            ->whereNotIn('status', [
                VehicleStatus::Inactive->value,
                VehicleStatus::Sold->value,
                VehicleStatus::Blocked->value,
                VehicleStatus::Scrapped->value,
            ])
            ->when($categoryId !== null, fn (Builder $query) => $query->where('vehicle_category_id', $categoryId))
            ->when($search !== null && trim($search) !== '', function (Builder $query) use ($search): void {
                $value = trim($search);
                $query->where(fn (Builder $scope) => $scope
                    ->where('vehicle_number', 'like', "%{$value}%")
                    ->orWhere('registration_number', 'like', "%{$value}%")
                    ->orWhere('chassis_number', 'like', "%{$value}%"));
            })
            ->whereNotIn('id', RentalVehicleAllocation::query()
                ->forContext($tenantId, $organizationUnitId)
                ->select('vehicle_id')
                ->whereIn('status', self::BLOCKING_ALLOCATION_STATUSES)
                ->where('allocated_from', '<', $endAt)
                ->where(fn (Builder $period) => $period->whereNull('allocated_to')->orWhere('allocated_to', '>', $startAt)))
            ->whereNotIn('id', RentalReservation::query()
                ->forContext($tenantId, $organizationUnitId)
                ->select('requested_vehicle_id')
                ->whereNotNull('requested_vehicle_id')
                ->whereIn('status', self::BLOCKING_RESERVATION_STATUSES)
                ->where('requested_start_at', '<', $endAt)
                ->where('requested_end_at', '>', $startAt))
            ->with(['make', 'model', 'type', 'category']);
    }
}
