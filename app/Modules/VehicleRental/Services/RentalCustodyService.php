<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalAssignmentData;
use Modules\VehicleRental\DTOs\RentalCustodyData;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Models\RentalCustodyEvent;
use Modules\VehicleRental\Services\Validation\RentalAssignmentSourceGuard;
use Modules\VehicleRental\Services\Validation\RentalAssignmentTimelineGuard;
use Modules\VehicleRental\Services\Validation\RentalRunningChartTimelineGuard;

final class RentalCustodyService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAssignmentTimelineGuard $timeline,
        private readonly RentalAssignmentSourceGuard $sources,
        private readonly RentalRunningChartTimelineGuard $runningCharts,
    ) {}

    public function record(
        RentalAssignment $assignment,
        RentalCustodyData $data,
        int $expectedVersion,
    ): RentalAssignment {
        return DB::transaction(function () use ($assignment, $data, $expectedVersion): RentalAssignment {
            $tenantId = (int) $assignment->tenant_id;
            $organizationUnitId = $assignment->organization_unit_id === null ? null : (int) $assignment->organization_unit_id;
            $assignment = RentalAssignment::query()
                ->forContext($tenantId, $organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($assignment->getKey());
            $this->timeline->assertExpectedVersion($assignment, $expectedVersion);
            $this->assertTransition($assignment, $data);
            $eventAt = $this->timeline->dateTime($data->eventAt);

            if ($data->eventType === RentalCustodyEventType::Handover) {
                $this->revalidateForHandover($assignment, $eventAt);
            }
            if ($data->eventType === RentalCustodyEventType::Return) {
                $this->runningCharts->assertNoChartsAfterClosure($assignment, $eventAt);
                $this->runningCharts->assertClosureOdometer($assignment, $eventAt, $data->odometer);
                if ($assignment->side === RentalAssignmentSide::OwnerSupply) {
                    $this->sources->assertNoActiveDependents($assignment);
                }
            }

            $this->appendEvent(
                $assignment,
                $data->eventType,
                $eventAt,
                $data->odometer,
                $data->fuelLevel,
                $data->conditionNotes,
                $data->damageNotes,
                $data->actorId,
            );

            $updates = ['row_version' => $expectedVersion + 1];
            if ($data->eventType === RentalCustodyEventType::Handover) {
                $updates += [
                    'status' => RentalAssignmentStatus::Active->value,
                    'handover_odometer' => $this->math->normalize($data->odometer),
                    'starts_at' => $eventAt->toDateTimeString(),
                ];
            } else {
                $updates += [
                    'status' => RentalAssignmentStatus::Returned->value,
                    'return_odometer' => $this->math->normalize($data->odometer),
                    'ends_at' => $eventAt->toDateTimeString(),
                    'closed_by' => $data->actorId,
                    'closed_at' => now(),
                ];
            }
            $assignment->forceFill($updates)->save();

            return $assignment->refresh()->load(RentalAssignmentService::RELATIONS);
        });
    }

    public function appendEvent(
        RentalAssignment $assignment,
        RentalCustodyEventType $type,
        CarbonImmutable $eventAt,
        string $odometer,
        ?string $fuelLevel,
        ?string $conditionNotes,
        ?string $damageNotes,
        ?int $actorId,
    ): void {
        $event = new RentalCustodyEvent();
        $event->forceFill([
            'tenant_id' => $assignment->tenant_id,
            'organization_unit_id' => $assignment->organization_unit_id,
            'assignment_id' => $assignment->getKey(),
            'event_type' => $type->value,
            'event_at' => $eventAt->toDateTimeString(),
            'odometer' => $this->math->normalize($odometer),
            'fuel_level' => $fuelLevel,
            'condition_notes' => $conditionNotes,
            'damage_notes' => $damageNotes,
            'created_by' => $actorId,
        ])->save();
    }

    private function assertTransition(RentalAssignment $assignment, RentalCustodyData $data): void
    {
        if ($data->eventType === RentalCustodyEventType::Handover
            && $assignment->status !== RentalAssignmentStatus::Planned) {
            throw new InvalidArgumentException('Only planned assignments can be handed over.');
        }
        if ($data->eventType === RentalCustodyEventType::Return
            && $assignment->status !== RentalAssignmentStatus::Active) {
            throw new InvalidArgumentException('Only active assignments can be returned.');
        }
        if ($data->eventType === RentalCustodyEventType::Return
            && $assignment->handover_odometer !== null
            && $this->math->compare($data->odometer, (string) $assignment->handover_odometer) < 0) {
            throw new InvalidArgumentException('Return odometer cannot be lower than the handover odometer.');
        }
        $eventAt = $this->timeline->dateTime($data->eventAt);
        if ($eventAt->lt($assignment->starts_at)
            || ($assignment->ends_at !== null && $eventAt->gt($assignment->ends_at))) {
            throw new InvalidArgumentException('Custody event must occur within the assignment period.');
        }
    }

    private function revalidateForHandover(RentalAssignment $assignment, CarbonImmutable $eventAt): void
    {
        $tenantId = (int) $assignment->tenant_id;
        $organizationUnitId = $assignment->organization_unit_id === null
            ? null
            : (int) $assignment->organization_unit_id;
        $agreement = RentalAgreement::query()
            ->forContext($tenantId, $organizationUnitId)
            ->lockForUpdate()
            ->findOrFail($assignment->agreement_id);
        $assignmentData = new RentalAssignmentData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            agreementId: (int) $assignment->agreement_id,
            vehicleId: (int) $assignment->vehicle_id,
            side: $assignment->side,
            startsAt: $eventAt->toDateTimeString(),
            endsAt: $assignment->ends_at?->toDateTimeString(),
            sourceAssignmentId: $assignment->source_assignment_id === null
                ? null
                : (int) $assignment->source_assignment_id,
            handoverOdometer: $assignment->handover_odometer === null
                ? null
                : (string) $assignment->handover_odometer,
            driverEmployeeId: $assignment->driver_employee_id === null
                ? null
                : (int) $assignment->driver_employee_id,
            selfDrive: (bool) $assignment->self_drive,
            actorId: null,
        );
        $endsAt = $assignment->ends_at === null ? null : CarbonImmutable::instance($assignment->ends_at);
        $this->sources->assertAgreementSupportsAssignment($agreement, $assignmentData, $eventAt, $endsAt);
        $this->timeline->lockVehicles($tenantId, $organizationUnitId, [(int) $assignment->vehicle_id]);
        $this->timeline->assertAvailable($tenantId, $organizationUnitId, (int) $assignment->vehicle_id, $eventAt, $endsAt);
        $this->timeline->assertDriverAvailable($assignmentData, $eventAt, $endsAt, (int) $assignment->getKey());
        $source = $this->sources->sourceAssignment($assignmentData, $eventAt, $endsAt);
        $this->sources->assertOwnershipSource($agreement, $assignmentData, $source, $eventAt, $endsAt);
    }
}
