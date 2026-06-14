<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleStatus;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Models\VehicleServiceJob;

class VehicleRentalAvailabilityService
{
    /**
     * @return array{available: bool, reasons: list<string>, vehicle: Vehicle}
     */
    public function check(
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        string $startAt,
        string $endAt,
        ?int $excludeAgreementId = null,
        ?int $excludeReservationId = null,
    ): array {
        [$start, $end] = $this->period($startAt, $endAt);
        $vehicle = Vehicle::query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $query->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->findOrFail($vehicleId);

        $reasons = [];
        if (! in_array($vehicle->status, [
            VehicleStatus::Active,
            VehicleStatus::Reserved,
            VehicleStatus::Rented,
        ], true)) {
            $reasons[] = match ($vehicle->status) {
                VehicleStatus::UnderService => 'Vehicle is in service or maintenance.',
                VehicleStatus::Inactive => 'Vehicle is inactive.',
                VehicleStatus::Blocked => 'Vehicle is manually blocked.',
                default => 'Vehicle is not operationally available.',
            };
        }

        $allocationExists = RentalAgreementVehicle::query()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', [
                RentalAgreementVehicleStatus::Allocated->value,
                RentalAgreementVehicleStatus::Active->value,
            ])
            ->when($excludeAgreementId !== null, fn (Builder $query) => $query->where('agreement_id', '!=', $excludeAgreementId))
            ->where('allocated_from', '<', $end)
            ->where(fn (Builder $query) => $query->whereNull('allocated_to')->orWhere('allocated_to', '>', $start))
            ->whereHas('agreement', fn (Builder $query) => $query->whereIn('status', [
                RentalAgreementStatus::Confirmed->value,
                RentalAgreementStatus::Active->value,
            ]))
            ->exists();
        if ($allocationExists) {
            $reasons[] = 'Vehicle is already allocated to an overlapping rental agreement.';
        }

        $reservationExists = RentalReservation::query()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', [
                RentalReservationStatus::Pending->value,
                RentalReservationStatus::Confirmed->value,
            ])
            ->when($excludeReservationId !== null, fn (Builder $query) => $query->whereKeyNot($excludeReservationId))
            ->where('start_at', '<', $end)
            ->where('expected_end_at', '>', $start)
            ->exists();
        if ($reservationExists) {
            $reasons[] = 'Vehicle is reserved for an overlapping period.';
        }

        $serviceExists = VehicleServiceJob::query()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', [
                VehicleServiceJobStatus::Inspected->value,
                VehicleServiceJobStatus::InProgress->value,
            ])
            ->whereDate('job_date', '<=', $end->toDateString())
            ->where(function (Builder $query) use ($start): void {
                $query->whereNull('expected_delivery_date')
                    ->orWhereDate('expected_delivery_date', '>=', $start->toDateString());
            })
            ->exists();
        if ($serviceExists) {
            $reasons[] = 'Vehicle has an overlapping service or maintenance job.';
        }

        return [
            'available' => $reasons === [],
            'reasons' => $reasons,
            'vehicle' => $vehicle,
        ];
    }

    public function assertAvailable(
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        string $startAt,
        string $endAt,
        ?int $excludeAgreementId = null,
        ?int $excludeReservationId = null,
    ): Vehicle {
        $result = $this->check(
            $tenantId,
            $organizationUnitId,
            $vehicleId,
            $startAt,
            $endAt,
            $excludeAgreementId,
            $excludeReservationId,
        );
        if (! $result['available']) {
            throw new InvalidArgumentException(implode(' ', $result['reasons']));
        }

        return $result['vehicle'];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function period(string $startAt, string $endAt): array
    {
        $start = CarbonImmutable::parse($startAt);
        $end = CarbonImmutable::parse($endAt);
        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Rental end date and time must be after the start date and time.');
        }

        return [$start, $end];
    }
}
