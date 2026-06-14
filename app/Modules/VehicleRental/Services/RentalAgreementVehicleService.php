<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;
use Modules\Vehicle\DTOs\VehicleStatusChangeData;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Services\VehicleStatusService;
use Modules\VehicleRental\DTOs\RentalAgreementVehicleData;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleStatus;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;

final class RentalAgreementVehicleService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAvailabilityService $availability,
        private readonly VehicleStatusService $vehicleStatuses,
        private readonly RentalAgreementVehicleLinkService $vehicleLinks,
    ) {}

    public function allocate(
        RentalAgreement $agreement,
        RentalAgreementVehicleData $data,
    ): RentalAgreementVehicle {
        return $this->createAllocation($agreement, $data);
    }

    private function createAllocation(
        RentalAgreement $agreement,
        RentalAgreementVehicleData $data,
        bool $allowActiveReplacement = false,
    ): RentalAgreementVehicle {
        return DB::transaction(function () use ($agreement, $data, $allowActiveReplacement): RentalAgreementVehicle {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $allowedStatuses = [
                RentalAgreementStatus::Draft,
                RentalAgreementStatus::Confirmed,
            ];
            if ($allowActiveReplacement) {
                $allowedStatuses[] = RentalAgreementStatus::Active;
            }
            if (! in_array($agreement->status, $allowedStatuses, true)) {
                throw new InvalidArgumentException('Vehicles can only be allocated to draft or confirmed agreements.');
            }
            $this->validatePeriod($agreement, $data);
            $vehicle = $this->availability->assertAvailable(
                (int) $agreement->tenant_id,
                $agreement->organization_unit_id,
                $data->vehicleId,
                $data->allocatedFrom,
                $data->allocatedTo ?? $agreement->expected_end_at->toDateTimeString(),
                excludeAgreementId: (int) $agreement->getKey(),
                excludeReservationId: $agreement->reservation_id,
                direction: $agreement->direction,
            );
            if ($this->math->isNegative($data->startOdometer)) {
                throw new InvalidArgumentException('Allocation start odometer cannot be negative.');
            }
            $this->validateOwner($agreement, $data->ownerPartyType, $data->ownerPartyId);

            $allocation = RentalAgreementVehicle::query()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'vehicle_id' => $vehicle->getKey(),
                'replaces_agreement_vehicle_id' => $data->replacesAgreementVehicleId,
                'owner_party_type' => $data->ownerPartyType?->value,
                'owner_party_id' => $data->ownerPartyId,
                'allocated_from' => $data->allocatedFrom,
                'allocated_to' => $data->allocatedTo,
                'start_odometer' => $this->math->normalize($data->startOdometer),
                'status' => RentalAgreementVehicleStatus::Allocated->value,
                'remarks' => $data->remarks,
                'created_by' => $data->createdBy,
                'updated_by' => $data->createdBy,
            ]);

            if ($agreement->status === RentalAgreementStatus::Confirmed
                && $vehicle->status === VehicleStatus::Active) {
                $this->vehicleStatuses->change($vehicle, new VehicleStatusChangeData(
                    VehicleStatus::Reserved,
                    'Reserved for rental agreement '.$agreement->agreement_number,
                ));
            }

            return $allocation->load('vehicle.make', 'vehicle.model');
        });
    }

    public function replace(
        RentalAgreement $agreement,
        RentalAgreementVehicle $current,
        RentalAgreementVehicleData $replacement,
    ): RentalAgreementVehicle {
        if ((int) $current->agreement_id !== (int) $agreement->getKey()) {
            throw new InvalidArgumentException('Replacement allocation does not belong to the agreement.');
        }
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Confirmed,
            RentalAgreementStatus::Active,
        ], true)) {
            throw new InvalidArgumentException('Vehicle replacement requires a confirmed or active agreement.');
        }

        return DB::transaction(function () use ($agreement, $current, $replacement): RentalAgreementVehicle {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $current = RentalAgreementVehicle::query()
                ->with('vehicle')
                ->lockForUpdate()
                ->findOrFail($current->getKey());
            $replacementAt = CarbonImmutable::parse($replacement->allocatedFrom);
            if ($replacementAt->lessThanOrEqualTo($current->allocated_from)) {
                throw new InvalidArgumentException('Replacement must occur after the current allocation starts.');
            }
            if ($current->usageLogs()
                ->where('effective_at', '>=', $replacementAt)
                ->exists()) {
                throw new InvalidArgumentException(
                    'Vehicle replacement cannot precede an existing running chart for the current allocation.',
                );
            }
            $this->vehicleLinks->supersedeForReplacement(
                $current,
                $replacementAt,
                $replacement->createdBy,
            );
            $current->forceFill([
                'allocated_to' => $replacement->allocatedFrom,
                'status' => RentalAgreementVehicleStatus::Replaced->value,
            ])->save();
            if ($current->vehicle?->status === VehicleStatus::Rented
                || $current->vehicle?->status === VehicleStatus::Reserved) {
                $this->vehicleStatuses->changeTo(
                    $current->vehicle,
                    VehicleStatus::Active,
                    reason: 'Replaced on rental agreement '.$agreement->agreement_number,
                );
            }

            $new = $this->createAllocation($agreement, new RentalAgreementVehicleData(
                vehicleId: $replacement->vehicleId,
                allocatedFrom: $replacement->allocatedFrom,
                startOdometer: $replacement->startOdometer,
                allocatedTo: $replacement->allocatedTo,
                ownerPartyType: $replacement->ownerPartyType,
                ownerPartyId: $replacement->ownerPartyId,
                remarks: $replacement->remarks,
                createdBy: $replacement->createdBy,
                replacesAgreementVehicleId: (int) $current->getKey(),
            ), true);
            if ($agreement->status === RentalAgreementStatus::Active) {
                $this->vehicleStatuses->changeTo(
                    $new->vehicle,
                    VehicleStatus::Rented,
                    reason: 'Replacement vehicle activated for rental agreement '.$agreement->agreement_number,
                );
                $new->forceFill(['status' => RentalAgreementVehicleStatus::Active->value])->save();
            }

            return $new->refresh()->load('vehicle.make', 'vehicle.model');
        });
    }

    private function validatePeriod(RentalAgreement $agreement, RentalAgreementVehicleData $data): void
    {
        $from = CarbonImmutable::parse($data->allocatedFrom);
        $to = CarbonImmutable::parse($data->allocatedTo ?? $agreement->expected_end_at);
        if ($to->lessThanOrEqualTo($from)) {
            throw new InvalidArgumentException('Vehicle allocation end must be after allocation start.');
        }
        if ($from->lessThan($agreement->start_at)) {
            throw new InvalidArgumentException('Vehicle allocation cannot start before the agreement.');
        }
    }

    private function validateOwner(
        RentalAgreement $agreement,
        ?RentalPartyType $ownerPartyType,
        ?int $ownerPartyId,
    ): void {
        if (($ownerPartyType === null) !== ($ownerPartyId === null)) {
            throw new InvalidArgumentException('Vehicle owner type and owner id must be provided together.');
        }
        if ($ownerPartyType === null || $ownerPartyId === null) {
            return;
        }
        if (! in_array($ownerPartyType, [RentalPartyType::Supplier, RentalPartyType::Owner], true)) {
            throw new InvalidArgumentException('Vehicle owner must be a supplier or owner.');
        }
        $owner = Supplier::query()
            ->where('tenant_id', $agreement->tenant_id)
            ->where(function (Builder $query) use ($agreement): void {
                $query->whereNull('organization_unit_id');
                if ($agreement->organization_unit_id !== null) {
                    $query->orWhere('organization_unit_id', $agreement->organization_unit_id);
                }
            })
            ->findOrFail($ownerPartyId);
        if ($owner->status !== SupplierStatus::Active) {
            throw new InvalidArgumentException('Vehicle owner must be active.');
        }
    }
}
