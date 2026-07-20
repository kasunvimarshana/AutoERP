<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalAssignmentData;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Services\Validation\RentalAssignmentSourceGuard;
use Modules\VehicleRental\Services\Validation\RentalAssignmentTimelineGuard;

final class RentalAssignmentService
{
    public const RELATIONS = [
        'agreement.customer',
        'agreement.supplier',
        'vehicle.model',
        'driver',
        'sourceAssignment.agreement.supplier',
        'replacesAssignment.vehicle.model',
        'custodyEvents',
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAssignmentTimelineGuard $timeline,
        private readonly RentalAssignmentSourceGuard $sources,
    ) {}

    public function create(RentalAssignmentData $data): RentalAssignment
    {
        return DB::transaction(function () use ($data): RentalAssignment {
            $sourcePreview = $data->sourceAssignmentId === null
                ? null
                : RentalAssignment::query()
                    ->forContext($data->tenantId, $data->organizationUnitId)
                    ->findOrFail($data->sourceAssignmentId);
            $agreementIds = array_values(array_unique(array_filter([
                $data->agreementId,
                $sourcePreview?->agreement_id,
            ])));
            sort($agreementIds, SORT_NUMERIC);
            $agreements = RentalAgreement::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->whereIn('id', $agreementIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $agreement = $agreements->get($data->agreementId);
            if (! $agreement instanceof RentalAgreement) {
                throw new InvalidArgumentException('Rental agreement was not found in the active scope.');
            }

            $startsAt = $this->timeline->dateTime($data->startsAt);
            $endsAt = $data->endsAt === null ? null : $this->timeline->dateTime($data->endsAt);
            $this->sources->assertAgreementSupportsAssignment($agreement, $data, $startsAt, $endsAt);
            $this->timeline->lockVehicles($data->tenantId, $data->organizationUnitId, [$data->vehicleId]);
            $this->timeline->assertAvailable($data->tenantId, $data->organizationUnitId, $data->vehicleId, $startsAt, $endsAt);
            $vehicleTimeline = $this->timeline->lockVehicleTimeline(
                $data->tenantId,
                $data->organizationUnitId,
                [$data->vehicleId],
                $data->side,
            );
            $this->timeline->assertNoVehicleOverlap($vehicleTimeline, $startsAt, $endsAt);
            $this->timeline->assertDriverAvailable($data, $startsAt, $endsAt);

            $source = $this->sources->sourceAssignmentForPlanning($data, $startsAt, $endsAt);
            $this->sources->assertOwnershipSource($agreement, $data, $source, $startsAt, $endsAt);

            $assignment = new RentalAssignment();
            $assignment->forceFill([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'agreement_id' => $agreement->getKey(),
                'vehicle_id' => $data->vehicleId,
                'source_assignment_id' => $source?->getKey(),
                'side' => $data->side->value,
                'status' => RentalAssignmentStatus::Planned->value,
                'starts_at' => $startsAt->toDateTimeString(),
                'ends_at' => $endsAt?->toDateTimeString(),
                'handover_odometer' => $data->handoverOdometer === null
                    ? null
                    : $this->math->normalize($data->handoverOdometer),
                'driver_employee_id' => $data->driverEmployeeId,
                'self_drive' => $data->selfDrive,
                'created_by' => $data->actorId,
            ])->save();

            return $this->load($assignment);
        });
    }

    public function cancel(RentalAssignment $assignment, int $expectedVersion, ?int $actorId): RentalAssignment
    {
        return DB::transaction(function () use ($assignment, $expectedVersion, $actorId): RentalAssignment {
            $assignment = RentalAssignment::query()->lockForUpdate()->findOrFail($assignment->getKey());
            $this->timeline->assertExpectedVersion($assignment, $expectedVersion);
            if ($assignment->status !== RentalAssignmentStatus::Planned) {
                throw new InvalidArgumentException('Only planned rental assignments can be cancelled.');
            }
            if ($assignment->side === RentalAssignmentSide::OwnerSupply) {
                $this->sources->assertNoActiveDependents($assignment);
            }
            $assignment->forceFill([
                'status' => RentalAssignmentStatus::Cancelled->value,
                'closed_by' => $actorId,
                'closed_at' => now(),
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $this->load($assignment);
        });
    }

    /** @return list<string> */
    public function relations(): array
    {
        return self::RELATIONS;
    }

    public function load(RentalAssignment $assignment): RentalAssignment
    {
        return $assignment->refresh()->load(self::RELATIONS);
    }
}
