<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class OdometerContinuityTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-10T12:00:00Z'));
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
        });
    }

    public function test_later_handover_cannot_ignore_a_previous_return_without_a_chart(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $first = $service->plan($context, $customer->id, $customer->row_version, $input);
            $first = $service->transition($context, $first->id, $first->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['starts_at'], 'reason' => 'Collected']);
            $service->transition($context, $first->id, $first->row_version, VehicleUseAction::ReturnVehicle, ['occurred_at' => $input['ends_at'], 'odometer' => '250', 'reason' => 'Returned']);
            $next = $service->plan($context, $customer->id, $customer->row_version, array_replace($input, ['starts_at' => $input['ends_at'], 'ends_at' => '2026-09-09T09:00:00+05:30']));
            try {
                $service->transition($context, $next->id, $next->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['ends_at'], 'odometer' => '249.999999', 'reason' => 'Collected']);
                self::fail('A later handover decreased the physical odometer.');
            } catch (ValidationException $error) {
                self::assertArrayHasKey('odometer', $error->errors());
                self::assertSame(VehicleUseStatus::Planned, $next->refresh()->status);
                self::assertSame(1, $next->history()->count());
            }
            $accepted = $service->transition($context, $next->id, $next->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['ends_at'], 'odometer' => '250', 'reason' => 'Verified at exchange']);
            self::assertSame('250.000000', $accepted->handover_odometer);
        });
    }

    public function test_backfilled_return_cannot_exceed_a_later_known_handover(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $laterInput = array_replace($input, ['starts_at' => $input['ends_at'], 'ends_at' => '2026-09-09T09:00:00+05:30']);
            $later = $service->plan($context, $customer->id, $customer->row_version, $laterInput);
            $later = $service->transition($context, $later->id, $later->row_version, VehicleUseAction::Handover, ['occurred_at' => $laterInput['starts_at'], 'odometer' => '200', 'reason' => 'Collected']);
            $service->transition($context, $later->id, $later->row_version, VehicleUseAction::ReturnVehicle, ['occurred_at' => $laterInput['ends_at'], 'reason' => 'Returned']);
            $earlier = $service->plan($context, $customer->id, $customer->row_version, $input);
            $earlier = $service->transition($context, $earlier->id, $earlier->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['starts_at'], 'reason' => 'Record earlier collection']);
            try {
                $service->transition($context, $earlier->id, $earlier->row_version, VehicleUseAction::ReturnVehicle, ['occurred_at' => $input['ends_at'], 'odometer' => '201', 'reason' => 'Record earlier return']);
                self::fail('Backfilled return exceeded a later handover.');
            } catch (ValidationException) {
                self::assertSame(VehicleUseStatus::InCustody, $earlier->refresh()->status);
                self::assertSame(2, $earlier->history()->count());
            }
        });
    }

    public function test_unknown_custody_readings_do_not_hide_previous_known_physical_evidence(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $first = $service->plan($context, $customer->id, $customer->row_version, $input);
            $first = $service->transition($context, $first->id, $first->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['starts_at'], 'reason' => 'Collected']);
            $service->transition($context, $first->id, $first->row_version, VehicleUseAction::ReturnVehicle, ['occurred_at' => $input['ends_at'], 'odometer' => '250', 'reason' => 'Returned']);
            $next = $service->plan($context, $customer->id, $customer->row_version, array_replace($input, ['starts_at' => $input['ends_at'], 'ends_at' => '2026-09-09T09:00:00+05:30']));
            $next = $service->transition($context, $next->id, $next->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['ends_at'], 'reason' => 'Reading not recorded']);
            self::assertNull($next->handover_odometer);
            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $next->id, $next->row_version, ['reference' => 'PARTIAL', 'starts_at' => '2026-09-08T10:00:00+05:30', 'ends_at' => '2026-09-08T11:00:00+05:30', 'end_odometer' => '249']);
            try {
                $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
                self::fail('Partial chart ignored a prior custody observation.');
            } catch (ValidationException) {
                self::assertSame('draft', $chart->refresh()->status->value);
                self::assertSame(1, $chart->history()->count());
                self::assertNull($chart->totalKm());
            }
        });
    }
}
