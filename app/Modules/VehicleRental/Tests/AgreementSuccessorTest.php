<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class AgreementSuccessorTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
        });
    }

    public function test_successor_draft_preserves_predecessor_until_effective_activation_then_closes_boundary_atomically(): void
    {
        [$context, $customer, $owner] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner): void {
            $service = app(AgreementService::class);
            foreach ([[AgreementKind::Customer, $customer], [AgreementKind::Owner, $owner]] as [$kind, $input]) {
                $record = $service->create($kind, $context, $input);
                $record = $service->change($kind, $context, $record->id, $record->row_version, AgreementAction::Activate);
                $successor = $service->successor($kind, $context, $record->id, $record->row_version, [
                    'reference' => $record->reference.'-R2',
                    'agreed_on' => '2026-09-20',
                    'starts_on' => '2026-10-01',
                    'ends_on' => null,
                    'reason' => 'Future commercial rate revision',
                ]);

                $record->refresh();
                self::assertSame(AgreementStatus::Active, $record->status);
                self::assertNull($record->ends_on);
                self::assertSame(2, $record->history()->count());
                self::assertSame(AgreementStatus::Draft, $successor->status);
                self::assertSame($record->id, $successor->supersedes_agreement_id);
                self::assertSame($record->basis, $successor->basis);
                self::assertSame($record->driver_mode, $successor->driver_mode);

                $this->travelTo(CarbonImmutable::parse('2026-10-01T00:00:00+00:00'));
                $successor = $service->change($kind, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
                $record->refresh();
                self::assertSame(AgreementStatus::Closed, $record->status);
                self::assertSame('2026-09-30', $record->ends_on->toDateString());
                self::assertSame(AgreementStatus::Active, $successor->status);
                self::assertSame(3, $record->history()->count());
                self::assertSame('2026-09-30', $record->history()->get()->last()->snapshot['ends_on']);
                self::assertSame(2, $successor->history()->count());
            }
        });
    }

    public function test_future_successor_cannot_be_activated_early(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-25T10:00:00+00:00'));
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $service = app(AgreementService::class);
            $record = $service->create(AgreementKind::Customer, $context, $input);
            $record = $service->change(AgreementKind::Customer, $context, $record->id, $record->row_version, AgreementAction::Activate);
            $successor = $service->successor(AgreementKind::Customer, $context, $record->id, $record->row_version, [
                'reference' => $record->reference.'-R2',
                'agreed_on' => '2026-09-25',
                'starts_on' => '2026-10-01',
                'reason' => 'Future rate revision',
            ]);

            try {
                $service->change(AgreementKind::Customer, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
                self::fail('Future successor activated before its effective date.');
            } catch (ValidationException) {
                self::assertSame(AgreementStatus::Active, $record->refresh()->status);
                self::assertSame(AgreementStatus::Draft, $successor->refresh()->status);
            }
        });
    }

    public function test_successor_activation_cannot_cut_through_existing_vehicle_use(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-08T12:00:00+00:00'));
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            app(VehicleUseService::class)->plan($context, $customer->id, $customer->row_version, $input);
            $successor = app(AgreementService::class)->successor(AgreementKind::Customer, $context, $customer->id, $customer->row_version, [
                'reference' => $customer->reference.'-R2',
                'agreed_on' => '2026-09-07',
                'starts_on' => '2026-09-08',
                'reason' => 'Rate revision',
            ]);
            self::assertSame(AgreementStatus::Active, $customer->refresh()->status);

            $this->expectException(ValidationException::class);
            app(AgreementService::class)->change(AgreementKind::Customer, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
        });
    }

    public function test_vehicle_use_ending_exactly_at_successor_boundary_is_allowed(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-08T12:00:00+00:00'));
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $input['ends_at'] = '2026-09-08T00:00:00+05:30';
            app(VehicleUseService::class)->plan($context, $customer->id, $customer->row_version, $input);
            $successor = app(AgreementService::class)->successor(AgreementKind::Customer, $context, $customer->id, $customer->row_version, [
                'reference' => $customer->reference.'-R2',
                'agreed_on' => '2026-09-07',
                'starts_on' => '2026-09-08',
                'reason' => 'Rate revision after completed planned period',
            ]);

            $successor = app(AgreementService::class)->change(AgreementKind::Customer, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
            self::assertSame(AgreementStatus::Active, $successor->status);
            self::assertSame('2026-09-07', $customer->refresh()->ends_on->toDateString());
        });
    }

    public function test_successor_activation_cannot_reclassify_an_existing_future_base_charge(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01T12:00:00+00:00'));
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $service = app(AgreementService::class);
            $record = $service->create(AgreementKind::Customer, $context, array_replace($input, ['terms' => ['base_rate' => '1000']]));
            $record = $service->change(AgreementKind::Customer, $context, $record->id, $record->row_version, AgreementAction::Activate);
            DB::table('vehicle_rental_customer_base_charges')->insert([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'agreement_id' => $record->id,
                'period_from' => '2026-09-20',
                'period_until' => '2026-10-05',
                'amount' => '1000.000000',
                'calculation' => json_encode(['policy' => 'test']),
                'actor_id' => $context->actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $successor = $service->successor(AgreementKind::Customer, $context, $record->id, $record->row_version, [
                'reference' => $record->reference.'-R2',
                'agreed_on' => '2026-09-20',
                'starts_on' => '2026-10-01',
                'reason' => 'Future rate revision',
            ]);

            $this->expectException(ValidationException::class);
            $service->change(AgreementKind::Customer, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
        });
    }

    public function test_successor_activation_cannot_reclassify_an_existing_usage_charge_period(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01T12:00:00+00:00'));
        $this->custodyFixture(function ($context, $use, $facts): void {
            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, $use->row_version, $facts);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            $customer = $use->customerAgreement()->firstOrFail();

            DB::table('vehicle_rental_customer_usage_charges')->insert([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'agreement_id' => $customer->id,
                'running_chart_id' => $chart->id,
                'row_version' => AgreementFields::INITIAL_VERSION,
                'component' => UsageChargeComponent::NormalOvertime->value,
                'period_from' => '2026-09-20',
                'period_until' => '2026-10-05',
                'amount' => '100.000000',
                'calculation' => json_encode(['policy' => 'test']),
                'actor_id' => $context->actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(AgreementService::class);
            $successor = $service->successor(AgreementKind::Customer, $context, $customer->id, $customer->row_version, [
                'reference' => $customer->reference.'-R2',
                'agreed_on' => '2026-09-20',
                'starts_on' => '2026-10-01',
                'reason' => 'Future rate revision',
            ]);

            $this->expectException(ValidationException::class);
            $service->change(AgreementKind::Customer, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
        });
    }

    public function test_security_deposit_requirement_is_not_implicitly_duplicated_to_successor(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $service = app(AgreementService::class);
            $record = $service->create(AgreementKind::Customer, $context, array_replace($input, ['terms' => ['deposit_requirement' => '5000']]));
            $record = $service->change(AgreementKind::Customer, $context, $record->id, $record->row_version, AgreementAction::Activate);
            $successor = $service->successor(AgreementKind::Customer, $context, $record->id, $record->row_version, [
                'reference' => $record->reference.'-R2',
                'agreed_on' => '2026-09-20',
                'starts_on' => '2026-10-01',
                'reason' => 'Future rate revision',
            ]);

            self::assertSame('5000.000000', $record->terms['deposit_requirement']);
            self::assertNull($successor->terms['deposit_requirement']);
        });
    }
}
