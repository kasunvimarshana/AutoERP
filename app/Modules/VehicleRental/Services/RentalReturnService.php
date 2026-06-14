<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Services\VehicleStatusService;
use Modules\VehicleRental\DTOs\RentalInspectionData;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalReturnInspection;

final class RentalReturnService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleStatusService $vehicleStatuses,
    ) {}

    public function save(
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
        RentalInspectionData $data,
    ): RentalReturnInspection {
        if ((int) $allocation->agreement_id !== (int) $agreement->getKey()) {
            throw new InvalidArgumentException('Return vehicle does not belong to the agreement.');
        }
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Active,
            RentalAgreementStatus::Returned,
        ], true)) {
            throw new InvalidArgumentException('Return inspection requires an active rental agreement.');
        }
        if (! $allocation->pickupInspection()->exists()) {
            throw new InvalidArgumentException('Pickup inspection is required before a vehicle can be returned.');
        }
        $startOdometer = (string) ($allocation->pickupInspection?->odometer ?? $allocation->start_odometer);
        if ($this->math->compare($data->odometer, $startOdometer) < 0) {
            throw new InvalidArgumentException('Return odometer cannot be below pickup odometer.');
        }
        if ($this->math->isNegative($data->damageAmount)) {
            throw new InvalidArgumentException('Damage amount cannot be negative.');
        }

        return DB::transaction(function () use ($agreement, $allocation, $data): RentalReturnInspection {
            $inspection = RentalReturnInspection::query()->updateOrCreate(
                ['agreement_vehicle_id' => $allocation->getKey()],
                [
                    'tenant_id' => $agreement->tenant_id,
                    'organization_unit_id' => $agreement->organization_unit_id,
                    'agreement_id' => $agreement->getKey(),
                    'vehicle_id' => $allocation->vehicle_id,
                    'inspected_at' => $data->inspectedAt,
                    'odometer' => $this->math->normalize($data->odometer),
                    'fuel_level' => $data->fuelLevel === null ? null : $this->math->normalize($data->fuelLevel),
                    'damage_amount' => $this->math->normalize($data->damageAmount),
                    'is_damage_billable' => $data->isDamageBillable,
                    'condition_notes' => $data->conditionNotes,
                    'damage_notes' => $data->damageNotes,
                    'attachments' => $data->attachments,
                    'inspected_by' => $data->inspectedBy,
                ],
            );
            $allocation->forceFill([
                'allocated_to' => $data->inspectedAt,
                'end_odometer' => $this->math->normalize($data->odometer),
                'status' => RentalAgreementVehicleStatus::Returned->value,
            ])->save();

            $vehicle = $allocation->vehicle()->lockForUpdate()->firstOrFail();
            if ($this->math->compare($data->odometer, (string) $vehicle->odometer_reading) > 0) {
                $vehicle->odometer_reading = $this->math->normalize($data->odometer);
                $vehicle->save();
            }
            if ($vehicle->status === VehicleStatus::Rented) {
                $this->vehicleStatuses->changeTo(
                    $vehicle,
                    VehicleStatus::Active,
                    $data->inspectedBy,
                    'Returned from rental agreement '.$agreement->agreement_number,
                );
            }
            if ($agreement->actual_end_at === null
                || $agreement->actual_end_at->lessThan($inspection->inspected_at)) {
                $agreement->actual_end_at = $inspection->inspected_at;
                $agreement->save();
            }

            return $inspection->refresh()->load('vehicle');
        });
    }
}
