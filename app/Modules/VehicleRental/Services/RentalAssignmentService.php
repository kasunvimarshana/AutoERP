<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
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
            $agreement = $this->lockedAgreement($data, $sourcePreview);
            $startsAt = $this->timeline->dateTime($data->startsAt);
            $endsAt = $data->endsAt === null ? null : $this->timeline->dateTime($data->endsAt);

            $this->validatePlan($data, $agreement, $startsAt, $endsAt);
            $source = $this->sources->sourceAssignmentForPlanning($data, $startsAt, $endsAt);
            $this->sources->assertOwnershipSource($agreement, $data, $source, $startsAt, $endsAt);

            $assignment = new RentalAssignment();
            $assignment->forceFill([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                ...$this->mutableAttributes($data, $agreement, $source, $startsAt, $endsAt),
                'status' => RentalAssignmentStatus::Planned->value,
                'created_by' => $data->actorId,
            ])->save();

            return $this->load($assignment);
        });
    }

    public function update(
        RentalAssignment $assignment,
        RentalAssignmentData $data,
        int $expectedVersion,
    ): RentalAssignment {
        return DB::transaction(function () use ($assignment, $data, $expectedVersion): RentalAssignment {
            $tenantId = (int) $assignment->tenant_id;
            $organizationUnitId = $assignment->organization_unit_id === null
                ? null
                : (int) $assignment->organization_unit_id;
            $assignment = RentalAssignment::query()
                ->forContext($tenantId, $organizationUnitId)
                ->whereKey($assignment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->timeline->assertExpectedVersion($assignment, $expectedVersion);
            $this->assertUnusedPlanned($assignment, 'edited');

            if ($data->tenantId !== $tenantId || $data->organizationUnitId !== $organizationUnitId) {
                throw new InvalidArgumentException('Rental assignment scope cannot be changed.');
            }
            if ($data->sourceAssignmentId !== null
                && $data->sourceAssignmentId === (int) $assignment->getKey()) {
                throw new InvalidArgumentException('A rental assignment cannot use itself as an owner-supply source.');
            }

            $sourcePreview = $data->sourceAssignmentId === null
                ? null
                : RentalAssignment::query()
                    ->forContext($tenantId, $organizationUnitId)
                    ->findOrFail($data->sourceAssignmentId);
            $agreement = $this->lockedAgreement($data, $sourcePreview);
            $startsAt = $this->timeline->dateTime($data->startsAt);
            $endsAt = $data->endsAt === null ? null : $this->timeline->dateTime($data->endsAt);

            $vehicleIds = array_values(array_unique([(int) $assignment->vehicle_id, $data->vehicleId]));
            $this->timeline->lockVehicles($tenantId, $organizationUnitId, $vehicleIds);
            $this->validatePlan(
                $data,
                $agreement,
                $startsAt,
                $endsAt,
                (int) $assignment->getKey(),
                vehiclesAlreadyLocked: true,
            );
            $source = $this->sources->sourceAssignmentForPlanning($data, $startsAt, $endsAt);
            $this->sources->assertOwnershipSource($agreement, $data, $source, $startsAt, $endsAt);

            $assignment->forceFill([
                ...$this->mutableAttributes($data, $agreement, $source, $startsAt, $endsAt),
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $this->load($assignment);
        });
    }

    public function deletePlanned(RentalAssignment $assignment, int $expectedVersion): void
    {
        DB::transaction(function () use ($assignment, $expectedVersion): void {
            $tenantId = (int) $assignment->tenant_id;
            $organizationUnitId = $assignment->organization_unit_id === null
                ? null
                : (int) $assignment->organization_unit_id;
            $assignment = RentalAssignment::query()
                ->forContext($tenantId, $organizationUnitId)
                ->whereKey($assignment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->timeline->assertExpectedVersion($assignment, $expectedVersion);
            $this->assertUnusedPlanned($assignment, 'deleted');
            $assignment->delete();
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

    private function lockedAgreement(
        RentalAssignmentData $data,
        ?RentalAssignment $sourcePreview,
    ): RentalAgreement {
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

        return $agreement;
    }

    private function validatePlan(
        RentalAssignmentData $data,
        RentalAgreement $agreement,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
        ?int $ignoreAssignmentId = null,
        bool $vehiclesAlreadyLocked = false,
    ): void {
        $this->sources->assertAgreementSupportsAssignment($agreement, $data, $startsAt, $endsAt);
        if (! $vehiclesAlreadyLocked) {
            $this->timeline->lockVehicles(
                $data->tenantId,
                $data->organizationUnitId,
                [$data->vehicleId],
            );
        }
        $this->timeline->assertAvailable(
            $data->tenantId,
            $data->organizationUnitId,
            $data->vehicleId,
            $startsAt,
            $endsAt,
        );
        $vehicleTimeline = $this->timeline->lockVehicleTimeline(
            $data->tenantId,
            $data->organizationUnitId,
            [$data->vehicleId],
            $data->side,
        );
        $this->timeline->assertNoVehicleOverlap(
            $vehicleTimeline,
            $startsAt,
            $endsAt,
            $ignoreAssignmentId,
        );
        $this->timeline->assertDriverAvailable(
            $data,
            $startsAt,
            $endsAt,
            $ignoreAssignmentId,
        );
    }

    /** @return array<string, mixed> */
    private function mutableAttributes(
        RentalAssignmentData $data,
        RentalAgreement $agreement,
        ?RentalAssignment $source,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
    ): array {
        return [
            'agreement_id' => $agreement->getKey(),
            'vehicle_id' => $data->vehicleId,
            'source_assignment_id' => $source?->getKey(),
            'side' => $data->side->value,
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt?->toDateTimeString(),
            'handover_odometer' => $data->handoverOdometer === null
                ? null
                : $this->math->normalize($data->handoverOdometer),
            'driver_employee_id' => $data->driverEmployeeId,
            'self_drive' => $data->selfDrive,
        ];
    }

    private function assertUnusedPlanned(RentalAssignment $assignment, string $action): void
    {
        if ($assignment->status !== RentalAssignmentStatus::Planned) {
            throw new InvalidArgumentException("Only planned rental assignments can be {$action}.");
        }

        $hasCustodyHistory = $assignment->custodyEvents()->lockForUpdate()->first() !== null;
        $hasRunningChartHistory = $assignment->runningCharts()->lockForUpdate()->first() !== null;
        $hasOwnReplacementHistory = $assignment->replaces_assignment_id !== null
            || $assignment->replacement_reason !== null;
        $hasAssignmentHistory = RentalAssignment::query()
            ->forContext(
                (int) $assignment->tenant_id,
                $assignment->organization_unit_id === null ? null : (int) $assignment->organization_unit_id,
            )
            ->where(function (Builder $query) use ($assignment): void {
                $query->where('source_assignment_id', $assignment->getKey())
                    ->orWhere('replaces_assignment_id', $assignment->getKey());
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->first() !== null;

        if ($hasCustodyHistory
            || $hasRunningChartHistory
            || $hasOwnReplacementHistory
            || $hasAssignmentHistory) {
            throw new InvalidArgumentException(
                "A rental assignment with custody, running-chart, replacement, or dependent assignment history cannot be {$action}.",
            );
        }
    }
}
