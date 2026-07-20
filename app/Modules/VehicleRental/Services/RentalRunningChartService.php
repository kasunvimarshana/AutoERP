<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalRunningChartData;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Models\RentalRunningChart;
use Modules\VehicleRental\Services\Validation\RentalRunningChartRateGuard;
use Modules\VehicleRental\Services\Validation\RentalRunningChartTimelineGuard;

final class RentalRunningChartService
{
    public const RELATIONS = [
        'assignment.agreement.customer',
        'assignment.sourceAssignment.agreement.supplier',
        'assignment.vehicle.model',
        'driver',
        'replacesRunningChart',
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalNumberService $numbers,
        private readonly RentalRunningChartTimelineGuard $timeline,
        private readonly RentalRunningChartRateGuard $rates,
    ) {}

    public function create(RentalRunningChartData $data): RentalRunningChart
    {
        return DB::transaction(function () use ($data): RentalRunningChart {
            $assignment = RentalAssignment::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($data->assignmentId);
            $startsAt = $this->timeline->startsAt($data);
            $endsAt = $this->timeline->endsAt($data);
            $this->timeline->assertAssignmentSupportsChart($assignment, $startsAt, $endsAt, true);
            $this->timeline->assertDriverFacts($assignment, $data);
            $this->timeline->assertNoActiveChartForDate($assignment, CarbonImmutable::parse($data->operationalDate));
            $this->timeline->assertNoOperationalOverlap($assignment, $startsAt, $endsAt);

            $replacement = RentalRunningChart::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->where('assignment_id', $assignment->getKey())
                ->whereDate('operational_date', CarbonImmutable::parse($data->operationalDate)->toDateString())
                ->where('status', RentalRunningChartStatus::Reversed->value)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            $chart = new RentalRunningChart();
            $chart->forceFill($this->attributes(
                $data,
                $assignment,
                $startsAt,
                $endsAt,
                $this->numbers->runningChart($data->tenantId, $data->organizationUnitId),
                $replacement?->getKey(),
            ))->save();

            return $this->load($chart);
        });
    }

    public function update(RentalRunningChart $chart, RentalRunningChartData $data, int $expectedVersion): RentalRunningChart
    {
        return DB::transaction(function () use ($chart, $data, $expectedVersion): RentalRunningChart {
            $chart = RentalRunningChart::query()
                ->forContext((int) $chart->tenant_id, $chart->organization_unit_id === null ? null : (int) $chart->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($chart->getKey());
            $this->assertExpectedVersion($chart, $expectedVersion);
            if ($chart->status !== RentalRunningChartStatus::Draft) {
                throw new InvalidArgumentException('Only draft running charts can be updated.');
            }
            if ($data->assignmentId !== (int) $chart->assignment_id) {
                throw new InvalidArgumentException('A running chart cannot move to another assignment.');
            }

            $assignment = RentalAssignment::query()
                ->forContext((int) $chart->tenant_id, $chart->organization_unit_id === null ? null : (int) $chart->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($chart->assignment_id);
            $startsAt = $this->timeline->startsAt($data);
            $endsAt = $this->timeline->endsAt($data);
            $this->timeline->assertAssignmentSupportsChart($assignment, $startsAt, $endsAt, false);
            $this->timeline->assertDriverFacts($assignment, $data);
            $this->timeline->assertNoActiveChartForDate(
                $assignment,
                CarbonImmutable::parse($data->operationalDate),
                (int) $chart->getKey(),
            );
            $this->timeline->assertNoOperationalOverlap($assignment, $startsAt, $endsAt, (int) $chart->getKey());

            $attributes = $this->attributes($data, $assignment, $startsAt, $endsAt, (string) $chart->chart_number, $chart->replaces_running_chart_id);
            unset($attributes['tenant_id'], $attributes['organization_unit_id'], $attributes['assignment_id'], $attributes['chart_number'], $attributes['replaces_running_chart_id'], $attributes['created_by'], $attributes['status'], $attributes['active_marker']);
            $chart->forceFill([...$attributes, 'row_version' => $expectedVersion + 1])->save();

            return $this->load($chart);
        });
    }

    public function finalize(RentalRunningChart $chart, int $expectedVersion, ?int $actorId): RentalRunningChart
    {
        return DB::transaction(function () use ($chart, $expectedVersion, $actorId): RentalRunningChart {
            $chart = RentalRunningChart::query()
                ->forContext((int) $chart->tenant_id, $chart->organization_unit_id === null ? null : (int) $chart->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($chart->getKey());
            $this->assertExpectedVersion($chart, $expectedVersion);
            if ($chart->status !== RentalRunningChartStatus::Draft) {
                throw new InvalidArgumentException('Only draft running charts can be finalized.');
            }

            $assignment = RentalAssignment::query()
                ->forContext((int) $chart->tenant_id, $chart->organization_unit_id === null ? null : (int) $chart->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($chart->assignment_id);
            $startsAt = CarbonImmutable::parse($chart->starts_at);
            $endsAt = CarbonImmutable::parse($chart->ends_at);
            $this->timeline->assertAssignmentSupportsChart($assignment, $startsAt, $endsAt, false);
            $this->timeline->assertNoOperationalOverlap($assignment, $startsAt, $endsAt, (int) $chart->getKey());
            $chart->setRelation('assignment', $assignment);
            $this->rates->assertCommercialMode($assignment, $chart);
            $this->timeline->assertOdometerContinuity($chart);

            $chart->forceFill([
                'status' => RentalRunningChartStatus::Finalized->value,
                'finalized_by' => $actorId,
                'finalized_at' => now(),
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $this->load($chart);
        });
    }

    public function reverse(RentalRunningChart $chart, int $expectedVersion, string $reason, ?int $actorId): RentalRunningChart
    {
        return DB::transaction(function () use ($chart, $expectedVersion, $reason, $actorId): RentalRunningChart {
            $chart = RentalRunningChart::query()
                ->forContext((int) $chart->tenant_id, $chart->organization_unit_id === null ? null : (int) $chart->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($chart->getKey());
            $this->assertExpectedVersion($chart, $expectedVersion);
            if ($chart->status !== RentalRunningChartStatus::Finalized) {
                throw new InvalidArgumentException('Only finalized running charts can be reversed.');
            }
            if (mb_strlen(trim($reason)) < 5) {
                throw new InvalidArgumentException('Running chart reversal reason must contain at least 5 characters.');
            }
            if ($chart->calculationSources()->where('active_marker', true)->lockForUpdate()->exists()) {
                throw new InvalidArgumentException('Cancel active customer and owner calculations before reversing this running chart.');
            }

            $chart->forceFill([
                'status' => RentalRunningChartStatus::Reversed->value,
                'active_marker' => null,
                'reversed_by' => $actorId,
                'reversed_at' => now(),
                'reversal_reason' => trim($reason),
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $this->load($chart);
        });
    }

    /** @return list<string> */
    public function relations(): array
    {
        return self::RELATIONS;
    }

    private function attributes(
        RentalRunningChartData $data,
        RentalAssignment $assignment,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        string $number,
        ?int $replacesChartId,
    ): array {
        $distances = $this->timeline->distances($data);

        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'chart_number' => $number,
            'assignment_id' => $assignment->getKey(),
            'replaces_running_chart_id' => $replacesChartId,
            'operational_date' => CarbonImmutable::parse($data->operationalDate)->toDateString(),
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
            'driver_employee_id' => $assignment->driver_employee_id,
            'ac_mode' => $data->acMode?->value,
            'start_odometer' => $this->math->normalize($data->startOdometer),
            'end_odometer' => $this->math->normalize($data->endOdometer),
            ...$distances,
            'normal_overtime_hours' => $this->nonNegative($data->normalOvertimeHours, 'Normal overtime'),
            'double_overtime_hours' => $this->nonNegative($data->doubleOvertimeHours, 'Double overtime'),
            'triple_overtime_hours' => $this->nonNegative($data->tripleOvertimeHours, 'Triple overtime'),
            'night_out_count' => $data->nightOutCount,
            'trip_origin' => $data->tripOrigin,
            'trip_destination' => $data->tripDestination,
            'purpose' => $data->purpose,
            'odometer_variance_reason' => $data->odometerVarianceReason,
            'remarks' => $data->remarks,
            'status' => RentalRunningChartStatus::Draft->value,
            'active_marker' => true,
            'created_by' => $data->actorId,
        ];
    }

    private function nonNegative(string $value, string $label): string
    {
        $normalized = $this->math->normalize($value);
        if ($this->math->isNegative($normalized)) {
            throw new InvalidArgumentException($label.' cannot be negative.');
        }

        return $normalized;
    }

    private function assertExpectedVersion(RentalRunningChart $chart, int $expectedVersion): void
    {
        if ((int) $chart->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Running chart was changed by another request. Reload it before continuing.');
        }
    }

    private function load(RentalRunningChart $chart): RentalRunningChart
    {
        return $chart->refresh()->load(self::RELATIONS);
    }
}
