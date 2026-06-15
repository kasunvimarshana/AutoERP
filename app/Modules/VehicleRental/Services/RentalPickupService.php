<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalInspectionData;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalPickupInspection;
use Modules\VehicleRental\Models\RentalUsageContext;

final class RentalPickupService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function save(
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
        RentalInspectionData $data,
    ): RentalPickupInspection {
        if ((int) $allocation->agreement_id !== (int) $agreement->getKey()) {
            throw new InvalidArgumentException('Pickup vehicle does not belong to the agreement.');
        }
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Confirmed,
            RentalAgreementStatus::Active,
        ], true)) {
            throw new InvalidArgumentException('Pickup inspection requires a confirmed or active agreement.');
        }
        if ($this->math->isNegative($data->odometer)
            || $this->math->compare($data->odometer, (string) $allocation->start_odometer) < 0) {
            throw new InvalidArgumentException('Pickup odometer cannot be below the allocated start odometer.');
        }

        return DB::transaction(function () use ($agreement, $allocation, $data): RentalPickupInspection {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            if (! in_array($agreement->status, [
                RentalAgreementStatus::Confirmed,
                RentalAgreementStatus::Active,
            ], true)) {
                throw new InvalidArgumentException('Pickup inspection requires a confirmed or active agreement.');
            }
            $allocation = RentalAgreementVehicle::query()->lockForUpdate()->findOrFail($allocation->getKey());
            if ((int) $allocation->agreement_id !== (int) $agreement->getKey()) {
                throw new InvalidArgumentException('Pickup vehicle does not belong to the agreement.');
            }
            $existing = RentalPickupInspection::query()
                ->where('agreement_vehicle_id', $allocation->getKey())
                ->lockForUpdate()
                ->first();
            if ($existing !== null && RentalUsageContext::query()
                ->where('agreement_vehicle_id', $allocation->getKey())
                ->exists()) {
                throw new InvalidArgumentException(
                    'Pickup inspection cannot be changed after running-chart usage has been recorded.',
                );
            }
            $inspection = RentalPickupInspection::query()->updateOrCreate(
                ['agreement_vehicle_id' => $allocation->getKey()],
                [
                    'tenant_id' => $agreement->tenant_id,
                    'organization_unit_id' => $agreement->organization_unit_id,
                    'agreement_id' => $agreement->getKey(),
                    'vehicle_id' => $allocation->vehicle_id,
                    'inspected_at' => $data->inspectedAt,
                    'odometer' => $this->math->normalize($data->odometer),
                    'fuel_level' => $data->fuelLevel === null ? null : $this->math->normalize($data->fuelLevel),
                    'condition_notes' => $data->conditionNotes,
                    'damage_notes' => $data->damageNotes,
                    'attachments' => $data->attachments,
                    'inspected_by' => $data->inspectedBy,
                ],
            );
            $allocation->forceFill([
                'start_odometer' => $this->math->normalize($data->odometer),
            ])->save();

            return $inspection->refresh()->load('vehicle');
        });
    }
}
