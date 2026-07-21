<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalRunningChartData;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Services\Validation\RentalRunningChartTimelineGuard;
use Tests\TestCase;

final class RentalRunningChartTimelineGuardTest extends TestCase
{
    public function test_distance_snapshot_excludes_garage_kilometres_from_commercial_usage(): void
    {
        $guard = new RentalRunningChartTimelineGuard(new DecimalMath());

        self::assertSame([
            'total_km' => '150.000000',
            'garage_km' => '20.000000',
            'commercial_km' => '130.000000',
        ], $guard->distances($this->data('1000', '1150', '20')));
    }

    public function test_unavailable_odometer_has_no_distance_values(): void
    {
        $guard = new RentalRunningChartTimelineGuard(new DecimalMath());

        self::assertSame([
            'total_km' => null,
            'garage_km' => null,
            'commercial_km' => null,
        ], $guard->distances($this->data(null, null, null)));
    }

    public function test_garage_kilometres_cannot_exceed_total_distance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Garage kilometres cannot exceed total kilometres');

        (new RentalRunningChartTimelineGuard(new DecimalMath()))
            ->distances($this->data('1000', '1010', '11'));
    }

    public function test_operational_date_must_match_start_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Operational date must match');

        $data = $this->data('1000', '1010', '0');
        $data = new RentalRunningChartData(
            tenantId: $data->tenantId,
            organizationUnitId: $data->organizationUnitId,
            assignmentId: $data->assignmentId,
            operationalDate: '2026-07-02',
            startsAt: $data->startsAt,
            endsAt: $data->endsAt,
            startOdometer: $data->startOdometer,
            endOdometer: $data->endOdometer,
            garageKm: $data->garageKm,
            normalOvertimeHours: $data->normalOvertimeHours,
            doubleOvertimeHours: $data->doubleOvertimeHours,
            tripleOvertimeHours: $data->tripleOvertimeHours,
            nightOutCount: $data->nightOutCount,
            acMode: null,
            tripOrigin: null,
            tripDestination: null,
            purpose: null,
            odometerVarianceReason: null,
            remarks: null,
            actorId: null,
        );

        (new RentalRunningChartTimelineGuard(new DecimalMath()))->startsAt($data);
    }

    public function test_offset_start_keeps_entered_operational_date_and_normalizes_to_utc(): void
    {
        $data = $this->data('1000', '1010', '0');
        $data = new RentalRunningChartData(
            tenantId: $data->tenantId,
            organizationUnitId: $data->organizationUnitId,
            assignmentId: $data->assignmentId,
            operationalDate: '2026-07-01',
            startsAt: '2026-07-01T00:30:00+05:30',
            endsAt: '2026-07-01T01:30:00+05:30',
            startOdometer: $data->startOdometer,
            endOdometer: $data->endOdometer,
            garageKm: $data->garageKm,
            normalOvertimeHours: $data->normalOvertimeHours,
            doubleOvertimeHours: $data->doubleOvertimeHours,
            tripleOvertimeHours: $data->tripleOvertimeHours,
            nightOutCount: $data->nightOutCount,
            acMode: null,
            tripOrigin: null,
            tripDestination: null,
            purpose: null,
            odometerVarianceReason: null,
            remarks: null,
            actorId: null,
        );

        $startsAt = (new RentalRunningChartTimelineGuard(new DecimalMath()))->startsAt($data);

        self::assertSame('UTC', $startsAt->getTimezone()->getName());
        self::assertSame('2026-06-30 19:00:00', $startsAt->toDateTimeString());
    }

    public function test_self_drive_assignment_rejects_driver_time_facts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Self-drive running charts');

        $assignment = new RentalAssignment();
        $assignment->forceFill([
            'side' => RentalAssignmentSide::CustomerUse->value,
            'status' => RentalAssignmentStatus::Active->value,
            'driver_employee_id' => null,
            'self_drive' => true,
        ]);
        $source = $this->data('1000', '1010', '0');
        $data = new RentalRunningChartData(
            tenantId: $source->tenantId,
            organizationUnitId: $source->organizationUnitId,
            assignmentId: $source->assignmentId,
            operationalDate: $source->operationalDate,
            startsAt: $source->startsAt,
            endsAt: $source->endsAt,
            startOdometer: $source->startOdometer,
            endOdometer: $source->endOdometer,
            garageKm: $source->garageKm,
            normalOvertimeHours: '1',
            doubleOvertimeHours: '0',
            tripleOvertimeHours: '0',
            nightOutCount: 0,
            acMode: null,
            tripOrigin: null,
            tripDestination: null,
            purpose: null,
            odometerVarianceReason: null,
            remarks: null,
            actorId: null,
        );

        (new RentalRunningChartTimelineGuard(new DecimalMath()))->assertDriverFacts($assignment, $data);
    }

    private function data(?string $start, ?string $end, ?string $garage): RentalRunningChartData
    {
        return new RentalRunningChartData(
            tenantId: 1,
            organizationUnitId: 1,
            assignmentId: 1,
            operationalDate: '2026-07-01',
            startsAt: '2026-07-01 08:00:00',
            endsAt: '2026-07-01 18:00:00',
            startOdometer: $start,
            endOdometer: $end,
            garageKm: $garage,
            normalOvertimeHours: '0',
            doubleOvertimeHours: '0',
            tripleOvertimeHours: '0',
            nightOutCount: 0,
            acMode: null,
            tripOrigin: null,
            tripDestination: null,
            purpose: null,
            odometerVarianceReason: null,
            remarks: null,
            actorId: null,
        );
    }
}
