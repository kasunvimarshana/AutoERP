<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\DTOs\RentalAssignmentData;
use Modules\VehicleRental\DTOs\RentalReplacementData;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Services\Validation\RentalAssignmentSourceGuard;
use Modules\VehicleRental\Services\Validation\RentalAssignmentTimelineGuard;
use Modules\VehicleRental\Services\Validation\RentalRunningChartTimelineGuard;

final class RentalReplacementService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAssignmentTimelineGuard $timeline,
        private readonly RentalAssignmentSourceGuard $sources,
        private readonly RentalCustodyService $custody,
        private readonly RentalRunningChartTimelineGuard $runningCharts,
    ) {}

    public function replace(
        RentalAssignment $original,
        RentalReplacementData $data,
        int $expectedVersion,
    ): RentalAssignment {
        return DB::transaction(function () use ($original, $data, $expectedVersion): RentalAssignment {
            $sourcePreview = $data->sourceAssignmentId === null
                ? null
                : RentalAssignment::query()
                    ->forContext($data->tenantId, $data->organizationUnitId)
                    ->findOrFail($data->sourceAssignmentId);
            $agreementIds = array_values(array_unique(array_filter([
                (int) $original->agreement_id,
                $sourcePreview?->agreement_id,
            ])));
            sort($agreementIds, SORT_NUMERIC);
            RentalAgreement::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->whereIn('id', $agreementIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $vehicleIds = [(int) $original->vehicle_id, $data->vehicleId];
            $this->timeline->lockVehicles($data->tenantId, $data->organizationUnitId, $vehicleIds);
            $vehicles = Vehicle::query()
                ->forTenant($data->tenantId, $data->organizationUnitId)
                ->whereIn('id', $vehicleIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (Vehicle $vehicle): int => (int) $vehicle->getKey());
            $oldVehicle = $vehicles->get((int) $original->vehicle_id);
            $newVehicle = $vehicles->get($data->vehicleId);
            if (! $oldVehicle instanceof Vehicle || ! $newVehicle instanceof Vehicle) {
                throw new InvalidArgumentException('Both replacement vehicles must be available in the current organization.');
            }
            $oldReturnOdometer = $this->resolveOdometer($oldVehicle, $data->oldReturnOdometer, 'Old vehicle');
            $newHandoverOdometer = $this->resolveOdometer($newVehicle, $data->newHandoverOdometer, 'Replacement vehicle');

            $this->timeline->lockVehicleTimeline(
                $data->tenantId,
                $data->organizationUnitId,
                $vehicleIds,
                $original->side,
            );
            $original = RentalAssignment::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($original->getKey());
            $this->timeline->assertExpectedVersion($original, $expectedVersion);
            if ($original->status !== RentalAssignmentStatus::Active) {
                throw new InvalidArgumentException('Only an active rental assignment can be replaced.');
            }
            if ($original->side === RentalAssignmentSide::OwnerSupply) {
                $this->sources->assertNoActiveDependents($original);
            }
            if ((int) $original->vehicle_id === $data->vehicleId) {
                throw new InvalidArgumentException('A replacement must select a different vehicle.');
            }
            if ($data->selfDrive && $data->driverEmployeeId !== null) {
                throw new InvalidArgumentException('Self-drive assignments cannot also have an assigned driver.');
            }

            $enteredEffectiveAt = CarbonImmutable::parse($data->effectiveAt);
            $effectiveAt = $this->timeline->dateTime($data->effectiveAt);
            $endsAt = $original->ends_at === null ? null : CarbonImmutable::instance($original->ends_at);
            if ($effectiveAt->lt($original->starts_at)) {
                throw new InvalidArgumentException('A replacement cannot start before the original assignment.');
            }
            if ($endsAt !== null && $effectiveAt->gt($endsAt)) {
                throw new InvalidArgumentException('A replacement must occur within the original assignment period.');
            }
            if ($original->handover_odometer !== null
                && $oldReturnOdometer !== null
                && $this->math->compare($oldReturnOdometer, (string) $original->handover_odometer) < 0) {
                throw new InvalidArgumentException('Replacement return odometer cannot be lower than the original handover odometer.');
            }
            $this->runningCharts->assertNoChartsAfterClosure($original, $effectiveAt);
            if ($oldReturnOdometer !== null) {
                $this->runningCharts->assertClosureOdometer($original, $effectiveAt, $oldReturnOdometer);
            }

            $agreement = RentalAgreement::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->findOrFail($original->agreement_id);
            $enteredEndsAt = $endsAt?->setTimezone($enteredEffectiveAt->getTimezone())->toIso8601String();
            $replacementData = new RentalAssignmentData(
                tenantId: $data->tenantId,
                organizationUnitId: $data->organizationUnitId,
                agreementId: (int) $agreement->getKey(),
                vehicleId: $data->vehicleId,
                side: $original->side,
                startsAt: $enteredEffectiveAt->toIso8601String(),
                endsAt: $enteredEndsAt,
                sourceAssignmentId: $data->sourceAssignmentId,
                handoverOdometer: $newHandoverOdometer,
                driverEmployeeId: $data->driverEmployeeId,
                selfDrive: $data->selfDrive,
                actorId: $data->actorId,
            );
            $this->sources->assertAgreementSupportsAssignment($agreement, $replacementData, $effectiveAt, $endsAt);
            $this->timeline->assertAvailable(
                $data->tenantId,
                $data->organizationUnitId,
                $data->vehicleId,
                $effectiveAt,
                $endsAt,
            );
            $newVehicleTimeline = $this->timeline->lockVehicleTimeline(
                $data->tenantId,
                $data->organizationUnitId,
                [$data->vehicleId],
                $original->side,
            );
            $this->timeline->assertNoVehicleOverlap($newVehicleTimeline, $effectiveAt, $endsAt);
            $this->timeline->assertDriverAvailable($replacementData, $effectiveAt, $endsAt, (int) $original->getKey());
            $source = $this->sources->sourceAssignmentForOperation($replacementData, $effectiveAt, $endsAt);
            $this->sources->assertOwnershipSource($agreement, $replacementData, $source, $effectiveAt, $endsAt);

            $replacement = new RentalAssignment();
            $replacement->forceFill([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'agreement_id' => $agreement->getKey(),
                'vehicle_id' => $data->vehicleId,
                'source_assignment_id' => $source?->getKey(),
                'replaces_assignment_id' => $original->getKey(),
                'side' => $original->side->value,
                'status' => RentalAssignmentStatus::Active->value,
                'starts_at' => $effectiveAt->toDateTimeString(),
                'ends_at' => $endsAt?->toDateTimeString(),
                'handover_odometer' => $newHandoverOdometer,
                'driver_employee_id' => $data->driverEmployeeId,
                'self_drive' => $data->selfDrive,
                'replacement_reason' => $data->reason,
                'created_by' => $data->actorId,
            ])->save();

            $this->custody->appendEvent(
                $original,
                RentalCustodyEventType::ReplacementOut,
                $effectiveAt,
                $oldReturnOdometer,
                $data->oldFuelLevel,
                $data->oldConditionNotes,
                $data->oldDamageNotes,
                $data->actorId,
            );
            $this->custody->appendEvent(
                $replacement,
                RentalCustodyEventType::ReplacementIn,
                $effectiveAt,
                $newHandoverOdometer,
                $data->newFuelLevel,
                $data->newConditionNotes,
                $data->newDamageNotes,
                $data->actorId,
            );

            $original->forceFill([
                'status' => RentalAssignmentStatus::Replaced->value,
                'return_odometer' => $oldReturnOdometer,
                'ends_at' => $effectiveAt->toDateTimeString(),
                'replacement_reason' => $data->reason,
                'closed_by' => $data->actorId,
                'closed_at' => now(),
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $replacement->refresh()->load(RentalAssignmentService::RELATIONS);
        });
    }

    private function resolveOdometer(Vehicle $vehicle, ?string $value, string $label): ?string
    {
        if ($vehicle->odometer_reading === null) {
            if ($value !== null) {
                throw new InvalidArgumentException($label.' has no available odometer. Leave its odometer field blank.');
            }

            return null;
        }

        if ($value === null) {
            throw new InvalidArgumentException($label.' odometer reading is required while its odometer is available.');
        }

        return $this->math->normalize($value);
    }
}
