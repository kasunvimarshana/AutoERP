<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseAvailabilityBlocker;
use Modules\VehicleRental\Services\VehicleUseService;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceJobType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Services\VehicleServiceStatusService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class VehicleUseTest extends TestCase
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
        });
    }

    public function test_use_freezes_both_agreement_revisions_and_preserves_actual_custody_history(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $s = app(VehicleUseService::class);
            $use = $s->plan($context, $customer->id, $customer->row_version, $input);
            self::assertSame(VehicleUseStatus::Planned, $use->status);
            self::assertSame('2026-09-07 03:30:00', $use->starts_at->format('Y-m-d H:i:s'));
            self::assertSame($customer->row_version, (int) $use->customer_agreement_version);
            self::assertSame($owner->row_version, (int) $use->owner_agreement_version);
            $use = $s->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['starts_at'], 'odometer' => '100', 'reason' => 'Customer collected vehicle']);
            self::assertSame(VehicleUseStatus::InCustody, $use->status);
            self::assertNotNull(app(VehicleUseAvailabilityBlocker::class)->blockingReason($context->tenantId, $context->organizationUnitId, $input['vehicle_id'], '2026-09-09 00:00:00', null));
            $use = $s->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, ['occurred_at' => $input['ends_at'], 'odometer' => '250', 'reason' => 'Vehicle returned']);
            self::assertSame(VehicleUseStatus::Returned, $use->status);
            self::assertSame(3, $use->history()->count());
            self::assertSame('planned', $use->history()->first()->snapshot['status']);
            self::assertNull(app(VehicleUseAvailabilityBlocker::class)->blockingReason($context->tenantId, null, $input['vehicle_id'], '2026-09-08 03:30:00', null));
            $this->expectException(\LogicException::class);
            $use->history()->first()->delete();
        });
    }

    public function test_overlap_is_tenant_wide_and_exact_return_boundary_is_available(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $s = app(VehicleUseService::class);
            $s->plan($context, $customer->id, $customer->row_version, $input);
            $branch = OrganizationUnitFixture::create(['tenant_id' => $context->tenantId, 'code' => 'SECOND', 'name' => 'Other branch']);
            self::assertNotNull(app(VehicleUseAvailabilityBlocker::class)->blockingReason($context->tenantId, $branch, $input['vehicle_id'], '2026-09-07 12:00:00', '2026-09-08 00:00:00'));
            try {
                $s->plan($context, $customer->id, $customer->row_version, $input);
                self::fail('Overlapping use accepted');
            } catch (ConflictHttpException) {
                $this->assertDatabaseCount('vehicle_rental_uses', 1);
            }
            $next = $s->plan($context, $customer->id, $customer->row_version, array_replace($input, ['starts_at' => $input['ends_at'], 'ends_at' => '2026-09-09T09:00:00+05:30']));
            self::assertSame(VehicleUseStatus::Planned, $next->status);
        });
    }

    public function test_missing_company_ownership_does_not_manufacture_company_supply(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $this->expectException(ValidationException::class);
            app(VehicleUseService::class)->plan($context, $customer->id, $customer->row_version, array_replace($input, ['owner_agreement_id' => null]));
        });
    }

    public function test_invalid_periods_stale_versions_and_closure_are_rejected_atomically(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $s = app(VehicleUseService::class);
            foreach ([['starts_at' => '2026-09-07 09:00:00'], ['ends_at' => $input['starts_at']], ['starts_at' => '2026-09-31T09:00:00+05:30']] as $bad) {
                try {
                    $s->plan($context, $customer->id, $customer->row_version, array_replace($input, $bad));
                    self::fail('Invalid period accepted');
                } catch (ValidationException) {
                    $this->assertDatabaseCount('vehicle_rental_uses', 0);
                }
            }
            $use = $s->plan($context, $customer->id, $customer->row_version, $input);
            foreach ([[AgreementKind::Customer, $customer], [AgreementKind::Owner, $owner]] as [$kind, $agreement]) {
                try {
                    app(AgreementService::class)->change($kind, $context, $agreement->id, $agreement->row_version, AgreementAction::Close, reason: 'End contract');
                    self::fail('Closed while use outstanding');
                } catch (ValidationException) {
                    self::assertSame('active', $agreement->refresh()->status->value);
                }
            }
            $s->transition($context, $use->id, $use->row_version, VehicleUseAction::Cancel, ['reason' => 'Booking withdrawn']);
            try {
                $s->transition($context, $use->id, $use->row_version, VehicleUseAction::Cancel, ['reason' => 'Stale request']);
                self::fail('Stale cancellation accepted');
            } catch (ConflictHttpException) {
                self::assertSame(2, $use->history()->count());
            }
        });
    }

    public function test_return_rejects_decreasing_odometer_without_history_mutation(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $s = app(VehicleUseService::class);
            $use = $s->plan($context, $customer->id, $customer->row_version, $input);
            $use = $s->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['starts_at'], 'odometer' => '100', 'reason' => 'Collected']);
            try {
                $s->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, ['occurred_at' => $input['ends_at'], 'odometer' => '99', 'reason' => 'Returned']);
                self::fail('Decreasing odometer accepted');
            } catch (ValidationException) {
                self::assertSame(2, $use->history()->count());
                self::assertSame(VehicleUseStatus::InCustody, $use->refresh()->status);
            }
        });
    }

    public function test_workshop_admission_cannot_steal_a_reserved_vehicle(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            app(VehicleUseService::class)->plan($context, $customer->id, $customer->row_version, $input);
            $job = new VehicleServiceJob;
            $job->forceFill(['tenant_id' => $context->tenantId, 'organization_unit_id' => $context->organizationUnitId, 'job_number' => 'RENTAL-CONFLICT', 'job_date' => '2026-09-07', 'expected_delivery_date' => '2026-09-08', 'vehicle_id' => $input['vehicle_id'], 'customer_id' => $customer->customer_id, 'bill_to_customer_id' => $customer->customer_id, 'status' => VehicleServiceJobStatus::Draft, 'type' => VehicleServiceJobType::FullService, 'row_version' => 1])->save();
            try {
                app(VehicleServiceStatusService::class)->change($job, VehicleServiceJobStatus::InProgress, $context->actorId, expectedVersion: $job->row_version);
                self::fail('Workshop stole vehicle');
            } catch (\InvalidArgumentException $e) {
                self::assertStringContainsString('Rental use', $e->getMessage());
                self::assertSame(VehicleServiceJobStatus::Draft, $job->refresh()->status);
            }
        });
    }

    public function test_api_requires_versions_and_exposes_readable_history(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $this->withoutMiddleware();
            $this->app->resolving(AgreementRequest::class, function ($request) use ($context): void {
                $request->attributes->set(config('core.current_tenant.id_attribute', 'current_tenant_id'), $context->tenantId);
                $request->attributes->set(config('core.current_organization_unit.id_attribute', 'current_organization_unit_id'), $context->organizationUnitId);
                $request->attributes->set(config('core.current_user.id_attribute', 'current_user_id'), $context->actorId);
            });
            $path = '/api/v1/vehicle-rental/customer/agreements/'.$customer->id.'/vehicles';
            $this->tenantPostJson($context->tenantId, $path, $input)->assertUnprocessable();
            $response = $this->tenantPostJson($context->tenantId, $path, array_merge($input, ['expected_version' => $customer->row_version]));
            $response->assertCreated()->assertJsonPath('data.customer_agreement.reference', $customer->reference);
            $id = $response->json('data.id');
            $this->tenantGetJson($context->tenantId, '/api/v1/vehicle-rental/vehicle-uses/'.$id.'/history')->assertOk()->assertJsonPath('data.0.action', 'plan')->assertJsonPath('data.0.actor.name', 'Test User');
        });
    }

    public function test_open_ended_plan_blocks_future_use_and_requires_open_source_coverage(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $input['ends_at'] = null;
            $use = $service->plan($context, $customer->id, $customer->row_version, $input);
            self::assertNull($use->ends_at);
            self::assertTrue(app(VehicleUseAvailabilityBlocker::class)->conflicts($context->tenantId, $input['vehicle_id'], '2027-01-01 00:00:00', null));
            $service->transition($context, $use->id, $use->row_version, VehicleUseAction::Cancel, ['reason' => 'Change to bounded supply']);
            // Simulate a source limit to verify that an unbounded request is never silently truncated.
            DB::table('vehicle_rental_owner_agreements')->where('id', $owner->id)->update(['ends_on' => '2026-09-09']);
            try {
                $service->plan($context, $customer->id, $customer->row_version, $input);
                self::fail('Unbounded use exceeded finite source');
            } catch (ValidationException) {
                $this->assertDatabaseCount('vehicle_rental_uses', 1);
            }
        });
    }

    public function test_replacement_rolls_back_return_on_invalid_supply_and_preserves_both_vehicles(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $old = $service->plan($context, $customer->id, $customer->row_version, $input);
            $old = $service->transition($context, $old->id, $old->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['starts_at'], 'reason' => 'Collected']);
            $vehicle = DB::table('vehicles')->insertGetId(['tenant_id' => $context->tenantId, 'vehicle_number' => 'REPLACEMENT', 'registration_number' => 'REPLACEMENT', 'status' => VehicleStatus::Active->value]);
            $replacement = array_replace($input, ['vehicle_id' => $vehicle, 'starts_at' => '2026-09-07T12:00:00+05:30', 'reason' => 'Vehicle exchange']);
            try {
                $service->replace($context, $old->id, $old->row_version, $replacement);
                self::fail('Used wrong owner agreement');
            } catch (ValidationException) {
                self::assertSame(VehicleUseStatus::InCustody, $old->refresh()->status);
                self::assertSame(2, $old->history()->count());
                $this->assertDatabaseCount('vehicle_rental_uses', 1);
            }
            DB::table('vehicle_ownerships')->insert(['tenant_id' => $context->tenantId, 'vehicle_id' => $vehicle, 'owner_type' => VehicleOwnerType::Company->value, 'owner_key' => 'company', 'owner_code_snapshot' => 'COMPANY', 'owner_name_snapshot' => 'Company', 'ownership_type' => VehicleOwnershipType::CompanyOwned->value, 'started_at' => '2026-09-01 00:00:00']);
            $replacement['owner_agreement_id'] = null;
            $next = $service->replace($context, $old->id, $old->row_version, $replacement);
            self::assertSame($old->id, (int) $next->replaces_use_id);
            self::assertSame(VehicleUseStatus::InCustody, $next->status);
            self::assertSame(VehicleUseStatus::Returned, $old->refresh()->status);
            self::assertSame($input['vehicle_id'], (int) $old->vehicle_id);
            self::assertSame($old->returned_at->toIso8601String(), $next->handed_over_at->toIso8601String());
            self::assertSame(3, $old->history()->count());
            self::assertSame(2, $next->history()->count());
        });
    }
}
