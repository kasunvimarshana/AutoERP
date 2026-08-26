<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Validation;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalRunningChartData;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Models\RentalRunningChart;

final class RentalRunningChartTimelineGuard
{
    public function __construct(private readonly DecimalMath $math) {}

    public function startsAt(RentalRunningChartData $data): CarbonImmutable
    {
        $enteredStartsAt = CarbonImmutable::parse($data->startsAt);
        if ($enteredStartsAt->toDateString() !== CarbonImmutable::parse($data->operationalDate)->toDateString()) {
            throw new InvalidArgumentException('Operational date must match the running chart start date.');
        }

        return $enteredStartsAt->utc();
    }

    public function endsAt(RentalRunningChartData $data): CarbonImmutable
    {
        $startsAt = $this->startsAt($data);
        $endsAt = CarbonImmutable::parse($data->endsAt)->utc();
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new InvalidArgumentException('Running chart end time must be after its start time.');
        }

        return $endsAt;
    }

    /** @return array{total_km:?string,garage_km:?string,commercial_km:?string} */
    public function distances(RentalRunningChartData $data): array
    {
        return $this->distanceValues($data->startOdometer, $data->endOdometer, $data->garageKm);
    }

    /** @return array{start_odometer:?string,end_odometer:?string,total_km:?string,garage_km:?string,commercial_km:?string} */
    public function measurements(
        RentalAssignment $assignment,
        RentalRunningChartData $data,
        CarbonImmutable $startsAt,
        ?RentalRunningChart $existingChart = null,
    ): array {
        $assignment->loadMissing('vehicle');
        $odometerAvailable = $existingChart instanceof RentalRunningChart
            ? $existingChart->start_odometer !== null
            : $assignment->vehicle?->odometer_reading !== null;

        if (! $odometerAvailable) {
            if ($data->startOdometer !== null || $data->endOdometer !== null || $data->garageKm !== null) {
                throw new InvalidArgumentException('This running chart has no available odometer. Leave the kilometre fields blank.');
            }

            return [
                'start_odometer' => null,
                'end_odometer' => null,
                'total_km' => null,
                'garage_km' => null,
                'commercial_km' => null,
            ];
        }

        if ($data->endOdometer === null) {
            throw new InvalidArgumentException('End odometer is required while this running chart uses odometer readings.');
        }

        $exceptChartId = $existingChart?->getKey();
        $start = $data->startOdometer ?? $this->suggestedStartOdometer(
            $assignment,
            $startsAt,
            $exceptChartId === null ? null : (int) $exceptChartId,
        );
        if ($start === null) {
            throw new InvalidArgumentException('Start odometer could not be resolved for this vehicle.');
        }

        return [
            'start_odometer' => $this->math->normalize($start),
            'end_odometer' => $this->math->normalize($data->endOdometer),
            ...$this->distanceValues($start, $data->endOdometer, $data->garageKm ?? '0'),
        ];
    }

    public function suggestedStartOdometer(
        RentalAssignment $assignment,
        CarbonImmutable $startsAt,
        ?int $exceptChartId = null,
    ): ?string {
        $assignment->loadMissing('vehicle');
        if ($assignment->vehicle?->odometer_reading === null) {
            return null;
        }

        $previous = $this->vehicleChartScope($assignment, $exceptChartId)
            ->where('ends_at', '<=', $startsAt->toDateTimeString())
            ->orderByDesc('ends_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($previous instanceof RentalRunningChart) {
            return $previous->end_odometer === null
                ? (string) $assignment->vehicle->odometer_reading
                : (string) $previous->end_odometer;
        }

        if ($assignment->handover_odometer !== null) {
            return (string) $assignment->handover_odometer;
        }

        return (string) $assignment->vehicle->odometer_reading;
    }

    public function assertDriverFacts(RentalAssignment $assignment, RentalRunningChartData $data): void
    {
        if ($assignment->driver_employee_id !== null) {
            return;
        }
        $hasDriverFacts = ! $this->math->isZero($data->normalOvertimeHours)
            || ! $this->math->isZero($data->doubleOvertimeHours)
            || ! $this->math->isZero($data->tripleOvertimeHours)
            || $data->nightOutCount > 0;
        if ($hasDriverFacts) {
            throw new InvalidArgumentException('Self-drive running charts cannot contain driver overtime or night-out facts.');
        }
    }

    public function assertAssignmentSupportsChart(
        RentalAssignment $assignment,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        bool $creating,
    ): void {
        if ($assignment->side !== RentalAssignmentSide::CustomerUse) {
            throw new InvalidArgumentException('Running charts belong to customer-use assignments only.');
        }
        if ($creating && $assignment->status !== RentalAssignmentStatus::Active) {
            throw new InvalidArgumentException('New running charts require an active customer-use assignment.');
        }
        if (in_array($assignment->status, [RentalAssignmentStatus::Planned, RentalAssignmentStatus::Cancelled], true)) {
            throw new InvalidArgumentException('The assignment is not operational for running-chart entry.');
        }
        if ($startsAt->lessThan(CarbonImmutable::parse($assignment->starts_at))) {
            throw new InvalidArgumentException('Running chart starts before the vehicle assignment.');
        }
        if ($assignment->ends_at !== null && $endsAt->greaterThan(CarbonImmutable::parse($assignment->ends_at))) {
            throw new InvalidArgumentException('Running chart ends after the vehicle assignment.');
        }
    }

    public function assertNoActiveChartForDate(
        RentalAssignment $assignment,
        CarbonImmutable $operationalDate,
        ?int $exceptChartId = null,
    ): void {
        $query = RentalRunningChart::query()
            ->forContext((int) $assignment->tenant_id, $assignment->organization_unit_id === null ? null : (int) $assignment->organization_unit_id)
            ->where('assignment_id', $assignment->getKey())
            ->whereDate('operational_date', $operationalDate->toDateString())
            ->where('active_marker', true);
        if ($exceptChartId !== null) {
            $query->where('id', '!=', $exceptChartId);
        }
        if ($query->lockForUpdate()->exists()) {
            throw new InvalidArgumentException('The assignment already has an active running chart for this operational date.');
        }
    }

    public function assertNoOperationalOverlap(
        RentalAssignment $assignment,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $exceptChartId = null,
    ): void {
        $query = RentalRunningChart::query()
            ->forContext((int) $assignment->tenant_id, $assignment->organization_unit_id === null ? null : (int) $assignment->organization_unit_id)
            ->where('active_marker', true)
            ->where('starts_at', '<', $endsAt->toDateTimeString())
            ->where('ends_at', '>', $startsAt->toDateTimeString())
            ->whereHas('assignment', fn (Builder $scope): Builder => $scope->where('vehicle_id', $assignment->vehicle_id));
        if ($exceptChartId !== null) {
            $query->where('id', '!=', $exceptChartId);
        }
        if ($query->lockForUpdate()->exists()) {
            throw new InvalidArgumentException('The vehicle already has an overlapping active running chart.');
        }

        if ($assignment->driver_employee_id === null) {
            return;
        }
        $driverQuery = RentalRunningChart::query()
            ->forContext((int) $assignment->tenant_id, $assignment->organization_unit_id === null ? null : (int) $assignment->organization_unit_id)
            ->where('active_marker', true)
            ->where('driver_employee_id', $assignment->driver_employee_id)
            ->where('starts_at', '<', $endsAt->toDateTimeString())
            ->where('ends_at', '>', $startsAt->toDateTimeString());
        if ($exceptChartId !== null) {
            $driverQuery->where('id', '!=', $exceptChartId);
        }
        if ($driverQuery->lockForUpdate()->exists()) {
            throw new InvalidArgumentException('The assigned driver already has an overlapping active running chart.');
        }
    }

    public function assertOdometerContinuity(RentalRunningChart $chart): void
    {
        if ($chart->start_odometer === null || $chart->end_odometer === null) {
            return;
        }

        $chart->loadMissing('assignment');
        $scope = $this->vehicleChartScope($chart->assignment, (int) $chart->getKey());
        $previous = (clone $scope)
            ->where('ends_at', '<=', $chart->starts_at)
            ->orderByDesc('ends_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
        $next = (clone $scope)
            ->where('starts_at', '>=', $chart->ends_at)
            ->orderBy('starts_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        $hasGap = false;
        if ($previous instanceof RentalRunningChart && $previous->end_odometer !== null) {
            if ($this->math->compare((string) $chart->start_odometer, (string) $previous->end_odometer) < 0) {
                throw new InvalidArgumentException('Start odometer is lower than the previous finalized running chart.');
            }
            $hasGap = $this->math->compare((string) $chart->start_odometer, (string) $previous->end_odometer) !== 0;
        }
        if ($next instanceof RentalRunningChart && $next->start_odometer !== null) {
            if ($this->math->compare((string) $chart->end_odometer, (string) $next->start_odometer) > 0) {
                throw new InvalidArgumentException('End odometer exceeds the next finalized running chart start odometer.');
            }
            $hasGap = $hasGap || $this->math->compare((string) $chart->end_odometer, (string) $next->start_odometer) !== 0;
        }
        if ($hasGap && trim((string) $chart->odometer_variance_reason) === '') {
            throw new InvalidArgumentException('Explain the odometer gap before finalizing the running chart.');
        }
    }

    public function assertClosureOdometer(RentalAssignment $assignment, CarbonImmutable $eventAt, string $odometer): void
    {
        $latest = $assignment->runningCharts()
            ->where('status', RentalRunningChartStatus::Finalized->value)
            ->where('active_marker', true)
            ->where('ends_at', '<=', $eventAt->toDateTimeString())
            ->orderByDesc('ends_at')
            ->lockForUpdate()
            ->first();
        if ($latest instanceof RentalRunningChart
            && $latest->end_odometer !== null
            && $this->math->compare($odometer, (string) $latest->end_odometer) < 0) {
            throw new InvalidArgumentException('Assignment closure odometer cannot be lower than the latest finalized running chart.');
        }
    }

    public function assertNoChartsAfterClosure(RentalAssignment $assignment, CarbonImmutable $eventAt): void
    {
        if ($assignment->runningCharts()
            ->where('active_marker', true)
            ->where(function (Builder $query) use ($eventAt): void {
                $query->where('starts_at', '>=', $eventAt->toDateTimeString())
                    ->orWhere('ends_at', '>', $eventAt->toDateTimeString());
            })
            ->lockForUpdate()
            ->exists()) {
            throw new InvalidArgumentException('Reverse or move running charts at or after the assignment closure time first.');
        }
    }

    /** @return array{total_km:?string,garage_km:?string,commercial_km:?string} */
    private function distanceValues(?string $startValue, ?string $endValue, ?string $garageValue): array
    {
        if ($startValue === null && $endValue === null) {
            if ($garageValue !== null) {
                throw new InvalidArgumentException('Garage kilometres require odometer readings.');
            }

            return ['total_km' => null, 'garage_km' => null, 'commercial_km' => null];
        }
        if ($startValue === null || $endValue === null) {
            throw new InvalidArgumentException('Start and end odometer readings must be supplied together.');
        }

        $start = $this->math->normalize($startValue);
        $end = $this->math->normalize($endValue);
        $garage = $this->math->normalize($garageValue ?? '0');
        if ($this->math->compare($end, $start) < 0) {
            throw new InvalidArgumentException('End odometer cannot be lower than start odometer.');
        }
        if ($this->math->isNegative($garage)) {
            throw new InvalidArgumentException('Garage kilometres cannot be negative.');
        }

        $total = $this->math->sub($end, $start);
        if ($this->math->compare($garage, $total) > 0) {
            throw new InvalidArgumentException('Garage kilometres cannot exceed total kilometres.');
        }

        return [
            'total_km' => $total,
            'garage_km' => $garage,
            'commercial_km' => $this->math->sub($total, $garage),
        ];
    }

    private function vehicleChartScope(RentalAssignment $assignment, ?int $exceptChartId = null): Builder
    {
        $query = RentalRunningChart::query()
            ->forContext((int) $assignment->tenant_id, $assignment->organization_unit_id === null ? null : (int) $assignment->organization_unit_id)
            ->where('status', RentalRunningChartStatus::Finalized->value)
            ->where('active_marker', true)
            ->whereHas('assignment', fn (Builder $scope): Builder => $scope->where('vehicle_id', $assignment->vehicle_id));

        if ($exceptChartId !== null) {
            $query->where('id', '!=', $exceptChartId);
        }

        return $query;
    }
}
