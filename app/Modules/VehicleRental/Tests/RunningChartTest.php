<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Tenancy\TenantFeature;
use Modules\User\Constants\UserGuard;
use Modules\User\Services\UserAccessResolver;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\VehicleUseService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

final class RunningChartTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-10T12:00:00+00:00'));
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
        });
    }

    private function custodyFixture(callable $work): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input) use ($work): void {
            $s = app(VehicleUseService::class);
            $use = $s->plan($context, $customer->id, $customer->row_version, $input);
            $use = $s->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['starts_at'], 'odometer' => '100', 'reason' => 'Collected']);
            $facts = ['reference' => 'CHART-A', 'starts_at' => '2026-09-07T09:00:15+05:30', 'ends_at' => '2026-09-07T17:00:30+05:30', 'start_odometer' => '100', 'end_odometer' => '150.25', 'garage_km' => '0', 'normal_ot_minutes' => 90];
            $work($context, $use, $facts);
        });
    }

    public function test_immutable_evidence_preserves_unknown_zero_seconds_and_correction_lineage(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $s = app(RunningChartService::class);
            $chart = $s->create($context, $use->id, $use->row_version, $facts);
            self::assertSame('50.250000', $chart->totalKm());
            self::assertNull($chart->commercial_km);
            self::assertSame('0.000000', $chart->garage_km);
            self::assertSame('2026-09-07 03:30:15', $chart->starts_at->format('Y-m-d H:i:s'));
            $chart = $s->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            try {
                $s->change($context, $chart->id, $chart->row_version, RunningChartAction::Update, $facts);
                self::fail('Edited finalized usage');
            } catch (ValidationException) {
                self::assertSame(2, $chart->history()->count());
            }
            $chart = $s->change($context, $chart->id, $chart->row_version, RunningChartAction::Reverse, reason: 'Correct signed chart reading');
            $next = $s->create($context, $use->id, $use->row_version, array_replace($facts, ['reference' => 'CORRECTION', 'end_odometer' => '155']), $chart->id);
            self::assertSame($chart->id, (int) $next->corrects_chart_id);
            self::assertSame('150.250000', $chart->history()->first()->snapshot['end_odometer']);
            try {
                $s->create($context, $use->id, $use->row_version, array_replace($facts, ['reference' => 'DUPLICATE']), $chart->id);
                self::fail('Duplicate correction');
            } catch (ConflictHttpException) {
                $this->assertDatabaseCount('vehicle_rental_running_charts', 2);
            }
        });
    }

    public function test_overlap_stale_actions_and_contradicting_return_are_atomic(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $s = app(RunningChartService::class);
            $first = $s->create($context, $use->id, $use->row_version, $facts);
            $second = $s->create($context, $use->id, $use->row_version, array_replace($facts, ['reference' => 'SECOND']));
            $s->change($context, $first->id, $first->row_version, RunningChartAction::Finalize);
            foreach ([[$second, RunningChartAction::Finalize], [$first, RunningChartAction::Reverse]] as [$chart, $action]) {
                try {
                    $s->change($context, $chart->id, $chart->row_version, $action, reason: 'Stale');
                    self::fail('Conflicting operation accepted');
                } catch (ConflictHttpException) {
                    self::assertSame(RunningChartStatus::Draft, $second->refresh()->status);
                }
            }
            foreach ([['occurred_at' => '2026-09-07T16:00:00+05:30'], ['occurred_at' => '2026-09-08T09:00:00+05:30', 'odometer' => '149']] as $return) {
                try {
                    app(VehicleUseService::class)->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, $return + ['reason' => 'Return']);
                    self::fail('Return contradicts finalized chart');
                } catch (ConflictHttpException) {
                    self::assertSame(2, $use->history()->count());
                }
            }
        });
    }

    public function test_invalid_observations_and_adjacent_odometer_regression_are_rejected(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $s = app(RunningChartService::class);
            foreach ([['end_odometer' => '99'], ['garage_km' => '51'], ['normal_ot_minutes' => '24.30'], ['starts_at' => '2026-09-07T08:00:00+05:30'], ['ends_at' => '2026-09-11T08:00:00+05:30'], ['starts_at' => '2026-09-31T08:00:00+05:30']] as $bad) {
                try {
                    $s->create($context, $use->id, $use->row_version, array_replace($facts, $bad));
                    self::fail('Invalid chart');
                } catch (ValidationException) {
                    $this->assertDatabaseCount('vehicle_rental_running_charts', 0);
                }
            }
            $first = $s->create($context, $use->id, $use->row_version, $facts);
            $s->change($context, $first->id, $first->row_version, RunningChartAction::Finalize);
            $second = $s->create($context, $use->id, $use->row_version, array_replace($facts, ['reference' => 'NEXT', 'starts_at' => $facts['ends_at'], 'ends_at' => '2026-09-07T18:00:00+05:30', 'start_odometer' => '149', 'end_odometer' => '160']));
            try {
                $s->change($context, $second->id, $second->row_version, RunningChartAction::Finalize);
                self::fail('Odometer moved backwards');
            } catch (ValidationException) {
                self::assertSame(1, $second->history()->count());
            }
        });
    }

    public function test_draft_permission_does_not_authorize_finalization(): void
    {
        [$context] = $this->fixture();
        $permission = DB::table('permissions')->insertGetId(['tenant_id' => $context->tenantId, 'name' => RentalAuthorization::CHART_MANAGE, 'guard_name' => UserGuard::TENANT_API, 'module' => TenantFeature::VEHICLE_RENTAL, 'is_active' => true]);
        DB::table('user_permissions')->insert(['tenant_id' => $context->tenantId, 'user_id' => $context->actorId, 'permission_id' => $permission]);
        $authorization = new RentalAuthorization(app(UserAccessResolver::class));
        $authorization->assertChart($context, RunningChartAction::Create, true);
        $this->expectException(AuthorizationException::class);
        $authorization->assertChart($context, RunningChartAction::Finalize, true);
    }

    public function test_known_end_cannot_precede_handover_when_start_is_unknown(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $chart = $service->create($context, $use->id, $use->row_version, array_replace($facts, ['start_odometer' => null, 'end_odometer' => '99']));
            $this->expectException(ValidationException::class);
            $service->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
        });
    }

    public function test_partial_adjacent_charts_cannot_hide_a_decreasing_reading(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $first = $service->create($context, $use->id, $use->row_version, array_replace($facts, ['start_odometer' => '150', 'end_odometer' => null]));
            $service->change($context, $first->id, $first->row_version, RunningChartAction::Finalize);
            $next = $service->create($context, $use->id, $use->row_version, array_replace($facts, ['reference' => 'AFTER-PARTIAL', 'starts_at' => $facts['ends_at'], 'ends_at' => '2026-09-07T18:00:00+05:30', 'start_odometer' => null, 'end_odometer' => '140']));
            $this->expectException(ValidationException::class);
            $service->change($context, $next->id, $next->row_version, RunningChartAction::Finalize);
        });
    }

    public function test_backfilled_partial_chart_cannot_exceed_a_later_known_end(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $later = $service->create($context, $use->id, $use->row_version, array_replace($facts, ['starts_at' => $facts['ends_at'], 'ends_at' => '2026-09-07T18:00:00+05:30', 'start_odometer' => null, 'end_odometer' => '180']));
            $service->change($context, $later->id, $later->row_version, RunningChartAction::Finalize);
            $earlier = $service->create($context, $use->id, $use->row_version, array_replace($facts, ['reference' => 'BEFORE-PARTIAL', 'start_odometer' => '190', 'end_odometer' => null]));
            $this->expectException(ValidationException::class);
            $service->change($context, $earlier->id, $earlier->row_version, RunningChartAction::Finalize);
        });
    }

    public function test_return_cannot_ignore_a_finalized_start_without_an_end_reading(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $chart = $service->create($context, $use->id, $use->row_version, array_replace($facts, ['start_odometer' => '150', 'end_odometer' => null]));
            $service->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            $this->expectException(ConflictHttpException::class);
            app(VehicleUseService::class)->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, ['occurred_at' => '2026-09-07T18:00:00+05:30', 'odometer' => '140', 'reason' => 'Contradicting reading']);
        });
    }

    public function test_valid_partial_observations_stay_unknown_after_finalization(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $chart = $service->create($context, $use->id, $use->row_version, array_replace($facts, ['start_odometer' => null, 'end_odometer' => '150']));
            $chart = $service->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            self::assertNull($chart->start_odometer);
            self::assertNull($chart->totalKm());
            self::assertSame('150.000000', $chart->end_odometer);
            self::assertNull($chart->history()->get()->last()->snapshot['start_odometer']);
        });
    }
}
