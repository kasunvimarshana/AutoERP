<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\Core\Tenancy\TenantFeature;
use Modules\User\Constants\UserGuard;
use Modules\User\Services\UserAccessResolver;
use Modules\VehicleRental\Enums\AirConditioningMode;
use Modules\VehicleRental\Enums\DriverIdentitySource;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Models\RunningChart;
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
            foreach ([['end_odometer' => '99'], ['garage_km' => '51'], ['normal_ot_minutes' => '24.30'], ['starts_at' => '2026-09-07T08:00:00+05:30'], ['ends_at' => '2026-09-07T08:30:00+05:30'], ['starts_at' => '2026-09-31T08:00:00+05:30']] as $bad) {
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

    public function test_api_can_fetch_single_chart_by_id(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $this->withoutMiddleware();
            $this->app->resolving(AgreementRequest::class, function ($request) use ($context): void {
                $request->attributes->set(config('core.current_tenant.id_attribute', 'current_tenant_id'), $context->tenantId);
                $request->attributes->set(config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'), $context->organizationUnitId);
                $request->attributes->set(config('core.current_user.id_attribute', 'current_user_id'), $context->actorId);
            });
            $service = app(RunningChartService::class);
            $chart = $service->create($context, $use->id, $use->row_version, $facts);

            $this->tenantGetJson($context->tenantId, '/api/v1/vehicle-rental/running-charts/'.$chart->id)
                ->assertOk()
                ->assertJsonPath('data.id', $chart->id)
                ->assertJsonPath('data.reference', $facts['reference']);
        });
    }

    public function test_reference_is_automatically_generated_when_omitted(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $factsWithoutReference = $facts;
            unset($factsWithoutReference['reference']);

            $chart = $service->create($context, $use->id, $use->row_version, $factsWithoutReference);
            self::assertMatchesRegularExpression('/^RC-2026-\d{5}$/', $chart->reference);
            self::assertSame('RC-2026-00001', $chart->reference);

            $second = $service->create($context, $use->id, $use->row_version, array_replace($factsWithoutReference, [
                'reference' => '   ',
                'starts_at' => '2026-09-07T17:00:30+05:30',
                'ends_at' => '2026-09-07T20:00:00+05:30',
            ]));
            self::assertSame('RC-2026-00002', $second->reference);
        });
    }

    public function test_numeric_odometer_and_distance_inputs_are_accepted(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $numericFacts = array_replace($facts, [
                'reference' => 'NUMERIC-CHART',
                'start_odometer' => 100,
                'end_odometer' => 150.25,
                'garage_km' => 0,
                'commercial_km' => 45.5,
            ]);

            $chart = $service->create($context, $use->id, $use->row_version, $numericFacts);
            self::assertSame('100.000000', $chart->start_odometer);
            self::assertSame('150.250000', $chart->end_odometer);
            self::assertSame('0.000000', $chart->garage_km);
            self::assertSame('45.500000', $chart->commercial_km);
            self::assertSame('50.250000', $chart->totalKm());
        });
    }

    public function test_running_chart_delete_is_strictly_prohibited(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $chart = $service->create($context, $use->id, $use->row_version, $facts);

            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('Preserve Running Chart evidence. Use reversal.');
            $chart->delete();
        });
    }

    public function test_corrected_by_chart_relation_and_resource_lineage(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $this->withoutMiddleware();
            $this->app->resolving(AgreementRequest::class, function ($request) use ($context): void {
                $request->attributes->set(config('core.current_tenant.id_attribute', 'current_tenant_id'), $context->tenantId);
                $request->attributes->set(config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'), $context->organizationUnitId);
                $request->attributes->set(config('core.current_user.id_attribute', 'current_user_id'), $context->actorId);
            });
            $service = app(RunningChartService::class);
            $chart = $service->create($context, $use->id, $use->row_version, $facts);
            $chart = $service->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            $chart = $service->change($context, $chart->id, $chart->row_version, RunningChartAction::Reverse, reason: 'Correct readings');

            $correction = $service->create($context, $use->id, $use->row_version, array_replace($facts, [
                'reference' => 'CORRECTION-RC',
                'end_odometer' => '152.0',
            ]), $chart->id);

            self::assertSame($correction->id, $chart->refresh()->correctedByChart?->id);
            self::assertSame($chart->id, $correction->correctsChart?->id);

            $this->tenantGetJson($context->tenantId, '/api/v1/vehicle-rental/running-charts/'.$chart->id)
                ->assertOk()
                ->assertJsonPath('data.corrected_by_chart.id', $correction->id)
                ->assertJsonPath('data.corrected_by_chart.reference', 'CORRECTION-RC');

            $this->tenantGetJson($context->tenantId, '/api/v1/vehicle-rental/running-charts/'.$correction->id)
                ->assertOk()
                ->assertJsonPath('data.corrects_chart.id', $chart->id)
                ->assertJsonPath('data.corrects_chart.reference', $chart->reference);
        });
    }

    public function test_optional_fields_can_be_omitted_or_passed_as_empty_strings(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $factsWithEmptyStrings = [
                'reference' => 'BLANK-OPTIONAL-CHART',
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-07T17:00:00+05:30',
                'start_odometer' => '',
                'end_odometer' => '',
                'garage_km' => '',
                'commercial_km' => '',
                'normal_ot_minutes' => '',
                'double_ot_minutes' => '',
                'triple_ot_minutes' => '',
                'night_outs' => '',
                'ac_mode' => '',
                'driver_identity_source' => '',
                'driver_employee_id' => '',
                'driver_name_snapshot' => '   ',
                'driver_reference_snapshot' => '',
                'driver_observation' => '   ',
                'notes' => '',
            ];

            $chart = $service->create($context, $use->id, $use->row_version, $factsWithEmptyStrings);
            self::assertNull($chart->start_odometer);
            self::assertNull($chart->end_odometer);
            self::assertNull($chart->garage_km);
            self::assertNull($chart->commercial_km);
            self::assertNull($chart->normal_ot_minutes);
            self::assertNull($chart->double_ot_minutes);
            self::assertNull($chart->triple_ot_minutes);
            self::assertNull($chart->night_outs);
            self::assertNull($chart->ac_mode);
            self::assertNull($chart->driver_identity_source);
            self::assertNull($chart->driver_employee_id);
            self::assertNull($chart->driver_name_snapshot);
            self::assertNull($chart->driver_reference_snapshot);
            self::assertNull($chart->driver_observation);
            self::assertNull($chart->notes);

            $updated = $service->change($context, $chart->id, $chart->row_version, RunningChartAction::Update, [
                'start_odometer' => '100.5',
                'end_odometer' => '',
                'garage_km' => '',
                'commercial_km' => '',
                'notes' => 'Updated notes',
            ]);
            self::assertSame('100.500000', $updated->start_odometer);
            self::assertNull($updated->end_odometer);
            self::assertSame('Updated notes', $updated->notes);
        });
    }

    public function test_draft_update_without_reference_retains_existing_reference(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $chart = $service->create($context, $use->id, $use->row_version, $facts);
            self::assertSame($facts['reference'], $chart->reference);

            $updated = $service->change($context, $chart->id, $chart->row_version, RunningChartAction::Update, [
                'start_odometer' => '105.000000',
                'notes' => 'Omitted reference test',
            ]);
            self::assertSame($facts['reference'], $updated->reference);
            self::assertSame('105.000000', $updated->start_odometer);
            self::assertSame('Omitted reference test', $updated->notes);
        });
    }

    public function test_running_chart_destroy_endpoint_returns_405_method_not_allowed(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $this->withoutMiddleware();
            $this->app->resolving(AgreementRequest::class, function ($request) use ($context): void {
                $request->attributes->set(config('core.current_tenant.id_attribute', 'current_tenant_id'), $context->tenantId);
                $request->attributes->set(config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'), $context->organizationUnitId);
                $request->attributes->set(config('core.current_user.id_attribute', 'current_user_id'), $context->actorId);
            });
            $service = app(RunningChartService::class);
            $chart = $service->create($context, $use->id, $use->row_version, $facts);

            $this->tenantDeleteJson($context->tenantId, '/api/v1/vehicle-rental/running-charts/'.$chart->id)
                ->assertStatus(405);

            $this->tenantDeleteJson($context->tenantId, '/api/v1/vehicle-rental/vehicle-uses/'.$use->id.'/running-charts/'.$chart->id)
                ->assertStatus(405);
        });
    }

    public function test_usage_period_validation_enforces_order_rejects_future_and_tolerates_clock_skew_in_workspace_timezone(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);

            // Rejects end earlier than or equal to start
            try {
                $service->create($context, $use->id, $use->row_version, array_replace($facts, [
                    'reference' => 'INVALID-ORDER',
                    'starts_at' => '2026-09-07T12:00:00+05:30',
                    'ends_at' => '2026-09-07T11:00:00+05:30',
                ]));
                self::fail('Usage end before start was accepted');
            } catch (ValidationException $e) {
                self::assertArrayHasKey('ends_at', $e->errors());
                self::assertSame('Usage end must follow usage start.', $e->errors()['ends_at'][0]);
            }

            // Current travel time is 2026-09-10T12:00:00+00:00 (17:30 in Asia/Colombo).
            // Permits future end date for draft running chart on ongoing custody
            $futureChart = $service->create($context, $use->id, $use->row_version, array_replace($facts, [
                'reference' => 'FUTURE-USAGE',
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-10T17:35:00+05:30',
            ]));
            self::assertSame(RunningChartStatus::Draft, $futureChart->status);

            // Rejects finalizing future usage before it has actually completed
            try {
                $service->change($context, $futureChart->id, $futureChart->row_version, RunningChartAction::Finalize);
                self::fail('Finalizing future usage was accepted');
            } catch (ValidationException $e) {
                self::assertArrayHasKey('ends_at', $e->errors());
                self::assertSame('Completed usage cannot end in the future.', $e->errors()['ends_at'][0]);
            }

            // Accepts usage ending at current time with 90-second clock skew
            $skewChart = $service->create($context, $use->id, $use->row_version, array_replace($facts, [
                'reference' => 'SKEW-CHART',
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-10T17:31:30+05:30',
            ]));
            self::assertSame('SKEW-CHART', $skewChart->reference);

            // Accepts past completed usage wholly within custody
            $pastChart = $service->create($context, $use->id, $use->row_version, array_replace($facts, [
                'reference' => 'PAST-CHART',
                'starts_at' => '2026-09-08T09:00:00+05:30',
                'ends_at' => '2026-09-08T12:00:00+05:30',
            ]));
            self::assertSame('PAST-CHART', $pastChart->reference);
        });
    }

    public function test_usage_coverage_accepts_sub_minute_handover_alignment_and_rejects_prior_period(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $useService = app(VehicleUseService::class);
            $chartService = app(RunningChartService::class);

            $use = $useService->plan($context, $customer->id, $customer->row_version, $input);
            // Handover occurred at 09:00:38+05:30
            $use = $useService->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T09:00:38+05:30',
                'odometer' => '100',
                'reason' => 'Collected with non-zero seconds',
            ]);

            // 1. User submits starts_at at 09:00:00 (within 1 minute before handover, e.g. operator keyed handover minute)
            $chart = $chartService->create($context, $use->id, $use->row_version, [
                'reference' => 'RC-MINUTE-ALIGN',
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-07T12:00:00+05:30',
                'start_odometer' => '100',
                'end_odometer' => '150',
            ]);
            self::assertSame('2026-09-07 03:30:38', $chart->starts_at->format('Y-m-d H:i:s'));

            // 2. User submits starts_at more than 1 minute before handover (08:55:00) -> rejected
            try {
                $chartService->create($context, $use->id, $use->row_version, [
                    'reference' => 'RC-TOO-EARLY',
                    'starts_at' => '2026-09-07T08:55:00+05:30',
                    'ends_at' => '2026-09-07T12:00:00+05:30',
                    'start_odometer' => '100',
                    'end_odometer' => '150',
                ]);
                self::fail('Usage before handover was accepted');
            } catch (ValidationException $e) {
                self::assertArrayHasKey('period', $e->errors());
                self::assertSame('Usage must fall within actual customer custody.', $e->errors()['period'][0]);
            }

            // Return vehicle at 18:00:15+05:30
            $returnedUse = $useService->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, [
                'occurred_at' => '2026-09-07T18:00:15+05:30',
                'odometer' => '200',
                'reason' => 'Returned with non-zero seconds',
            ]);

            // 3. User submits ends_at with sub-minute skew after return (18:00:30) -> accepted and aligned
            $returnSkewChart = $chartService->create($context, $returnedUse->id, $returnedUse->row_version, [
                'reference' => 'RC-RETURN-SKEW',
                'starts_at' => '2026-09-07T15:00:00+05:30',
                'ends_at' => '2026-09-07T18:00:30+05:30',
                'start_odometer' => '160',
                'end_odometer' => '200',
            ]);
            self::assertSame('2026-09-07 12:30:15', $returnSkewChart->ends_at->format('Y-m-d H:i:s'));

            // 4. User submits ends_at 10 minutes after return -> rejected
            try {
                $chartService->create($context, $returnedUse->id, $returnedUse->row_version, [
                    'reference' => 'RC-AFTER-RETURN',
                    'starts_at' => '2026-09-07T15:00:00+05:30',
                    'ends_at' => '2026-09-07T18:10:00+05:30',
                    'start_odometer' => '160',
                    'end_odometer' => '200',
                ]);
                self::fail('Usage after return was accepted');
            } catch (ValidationException $e) {
                self::assertArrayHasKey('period', $e->errors());
                self::assertSame('Usage must fall within actual customer custody.', $e->errors()['period'][0]);
            }
        });
    }
}
