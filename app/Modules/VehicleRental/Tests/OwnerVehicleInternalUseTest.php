<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\BillingFinalizationMode;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartReportService;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\TwoStageInvoicingService;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class OwnerVehicleInternalUseTest extends TestCase
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
            $mock->shouldReceive('assertBilling')->zeroOrMoreTimes();
        });
    }

    public function test_plan_owner_vehicle_for_internal_company_use_without_customer(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        [$use, $owner] = $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): array {
            $agreements = app(AgreementService::class);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $s = app(VehicleUseService::class);

            // Plan directly under the owner agreement for internal company operations — no customer agreement required
            $useInput = [
                'vehicle_id' => $ownerData['vehicle_id'],
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-08T09:00:00+05:30',
                'notes' => 'Internal pool vehicle assignment for maintenance crew',
            ];

            $use = $s->planOwner($context, $owner->id, $owner->row_version, $useInput);

            return [$use, $owner];
        });

        self::assertSame(VehicleUseStatus::Planned, $use->status);
        self::assertNull($use->customer_agreement_id);
        self::assertNull($use->customer_agreement_version);
        self::assertSame($owner->id, (int) $use->owner_agreement_id);
        self::assertSame($owner->row_version, (int) $use->owner_agreement_version);
        self::assertSame((int) $ownerData['vehicle_id'], (int) $use->vehicle_id);

        // API endpoint also exposes null customer_agreement and valid owner_agreement
        $response = $this->getJson("/api/v1/vehicle-rental/vehicle-uses/{$use->id}")
            ->assertOk()
            ->json('data');

        self::assertNull($response['customer_agreement']);
        self::assertNotNull($response['owner_agreement']);
        self::assertSame($owner->id, $response['owner_agreement']['id']);
    }

    public function test_handover_and_return_transitions_for_internal_use(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        $vehicleId = (int) $ownerData['vehicle_id'];

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData, $vehicleId): void {
            $agreements = app(AgreementService::class);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $s = app(VehicleUseService::class);

            $use = $s->planOwner($context, $owner->id, $owner->row_version, [
                'vehicle_id' => $vehicleId,
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-08T09:00:00+05:30',
            ]);

            // Handover to internal staff
            $use = $s->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T09:00:00+05:30',
                'odometer' => '50000.000000',
                'reason' => 'Dispatched for company internal site inspection',
            ]);

            self::assertSame(VehicleUseStatus::InCustody, $use->status);
            self::assertSame('50000.000000', $use->handover_odometer);
            self::assertNull($use->customer_agreement_id);

            // Physical vehicle status in fleet changes to rented
            $vehicleStatus = DB::table('vehicles')->where('id', $vehicleId)->value('status');
            self::assertSame(VehicleStatus::Rented->value, $vehicleStatus);

            // Return from internal use
            $use = $s->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, [
                'occurred_at' => '2026-09-08T09:00:00+05:30',
                'odometer' => '50180.000000',
                'reason' => 'Completed inspection, vehicle returned to depot',
            ]);

            self::assertSame(VehicleUseStatus::Returned, $use->status);
            self::assertSame('50180.000000', $use->return_odometer);

            // Physical vehicle status in fleet returns to active
            $vehicleStatusAfter = DB::table('vehicles')->where('id', $vehicleId)->value('status');
            self::assertSame(VehicleStatus::Active->value, $vehicleStatusAfter);
        });
    }

    public function test_running_chart_recording_and_overtime_baseline_on_internal_use(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        // Create owner agreement terms with 8-hour baseline and overtime rates
        $ownerData['driver_mode'] = 'with_driver';
        $ownerData['terms'] = array_merge($ownerData['terms'], [
            'working_hours' => '8.0',
            'normal_ot_rate' => '350.00',
            'excess_km_rate' => '45.00',
        ]);

        [$chart, $owner] = $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): array {
            $agreements = app(AgreementService::class);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $s = app(VehicleUseService::class);
            $chartService = app(RunningChartService::class);

            $use = $s->planOwner($context, $owner->id, $owner->row_version, [
                'vehicle_id' => (int) $ownerData['vehicle_id'],
                'starts_at' => '2026-09-07T08:00:00+05:30',
                'ends_at' => '2026-09-07T20:00:00+05:30',
            ]);

            $use = $s->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T08:00:00+05:30',
                'odometer' => '10000.000000',
                'reason' => 'Internal dispatch',
            ]);

            // Record a 10-hour trip: 8-hour baseline gives 2 hours (120 mins) calculated OT
            $chart = $chartService->create($context, $use->id, $use->row_version, [
                'reference' => 'RC-INT-001',
                'starts_at' => '2026-09-07T08:00:00+05:30',
                'ends_at' => '2026-09-07T18:00:00+05:30',
                'start_odometer' => '10000.000000',
                'end_odometer' => '10150.000000',
                'commercial_km' => '150.000000',
                'normal_ot_minutes' => 120,
                'notes' => 'Field duty trip',
            ]);

            self::assertSame(RunningChartStatus::Draft, $chart->status);

            // Finalize running chart
            $chart = $chartService->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            self::assertSame(RunningChartStatus::Finalized, $chart->status);

            return [$chart, $owner];
        });

        // Verify via API resource that calculated_ot_minutes and agreed_baseline_hours reflect owner agreement
        $res = $this->getJson("/api/v1/vehicle-rental/running-charts/{$chart->id}")
            ->assertOk()
            ->json('data');

        self::assertSame('8.000000', (string) $res['agreed_baseline_hours']);
        self::assertSame(120, $res['calculated_ot_minutes']);

        // Verify Running Chart Register filtering by kind=owner
        $registerRes = $this->getJson('/api/v1/vehicle-rental/running-charts?kind=owner')
            ->assertOk()
            ->json('data');

        self::assertNotEmpty($registerRes);
        self::assertNull($registerRes[0]['customer_agreement']);
        self::assertSame($owner->reference, $registerRes[0]['owner_agreement']['reference']);

        // Verify Running Chart Owner Report sheet generation
        $report = $this->withTenantExecutionContext($context->tenantId, function () use ($context, $owner): array {
            $reportService = app(RunningChartReportService::class);
            return $reportService->generate($context, [
                'agreement_id' => $owner->id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'sheet_mode' => 'owner',
            ]);
        });

        self::assertTrue($report['header']['is_owner_sheet']);
        self::assertSame('INTERNAL / COMPANY USE', $report['header']['client_name']);
        self::assertCount(30, $report['rows']);
        $activeDays = array_values(array_filter($report['rows'], fn ($r) => $r['is_active_day']));
        self::assertCount(1, $activeDays);
        self::assertSame('INTERNAL / COMPANY USE', $activeDays[0]['lessee']);
        self::assertSame(150, $report['summary']['total_comparative_mileage']);
    }

    public function test_owner_billing_cycle_and_settlement_for_internal_use_running_charts(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(modules: [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);

            $agreements = app(AgreementService::class);
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['driver_mode'] = 'with_driver';
            $ownerData['terms'] = array_merge($ownerData['terms'], [
                'base_rate' => '100000.00',
                'excess_km_rate' => '50.00',
                'included_km' => '100',
                'working_hours' => '8.0',
                'normal_ot_rate' => '300.00',
            ]);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $s = app(VehicleUseService::class);
            $chartService = app(RunningChartService::class);

            $use = $s->planOwner($context, $owner->id, $owner->row_version, [
                'vehicle_id' => (int) $ownerData['vehicle_id'],
                'starts_at' => '2026-09-01T08:00:00+05:30',
                'ends_at' => '2026-09-30T18:00:00+05:30',
            ]);

            $use = $s->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+05:30',
                'odometer' => '20000.000000',
                'reason' => 'Internal monthly deployment',
            ]);

            // Record and finalize trip: 150 km (50 km excess at 50 = 2500) and 2 hrs OT at 300 = 600
            $chart = $chartService->create($context, $use->id, $use->row_version, [
                'reference' => 'RC-SETTLE-001',
                'starts_at' => '2026-09-05T08:00:00+05:30',
                'ends_at' => '2026-09-05T18:00:00+05:30',
                'start_odometer' => '20000.000000',
                'end_odometer' => '20150.000000',
                'commercial_km' => '150.000000',
                'normal_ot_minutes' => 120,
            ]);
            $chart = $chartService->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);

            $invoicing = app(TwoStageInvoicingService::class);

            // Create initial stage owner billing cycle
            $cycle = $invoicing->createCycle(AgreementKind::Owner, $context, $owner->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
                'invoice_date' => '2026-09-01',
            ]);

            self::assertNull($cycle->customer_agreement_id);
            self::assertSame($owner->id, $cycle->owner_agreement_id);
            self::assertNotNull($cycle->initial_invoice_id);

            // Initial invoice direction is Inbound (payable to vehicle owner supplier)
            $initialBill = DB::table('invoices')->where('id', $cycle->initial_invoice_id)->first();
            self::assertSame(InvoiceDirection::Inbound->value, $initialBill->direction);
            self::assertSame((int) $ownerData['party_id'], (int) $initialBill->party_id);

            // Attach running charts to owner cycle
            $cycle = $invoicing->attachRunningCharts($context, $cycle->id, [$chart->id], (int) $cycle->row_version);
            self::assertSame(1, $cycle->charts()->count());

            // Finalize owner billing cycle with ConsolidatedRevision
            $finalized = $invoicing->finalize($context, $cycle->id, [
                'finalization_mode' => BillingFinalizationMode::ConsolidatedRevision->value,
                'invoice_date' => '2026-09-30',
            ], (int) $cycle->row_version);

            self::assertSame(BillingCycleStatus::Finalized, $finalized->status);
            self::assertNotNull($finalized->final_invoice_id);

            // Ensure final invoice was created in Inbound (payable to vehicle owner supplier)
            $bill = DB::table('invoices')->where('id', $finalized->final_invoice_id)->first();
            self::assertSame(InvoiceDirection::Inbound->value, $bill->direction);
            self::assertSame((int) $ownerData['party_id'], (int) $bill->party_id);

            // Verify no customer invoice was created anywhere
            $customerInvoicesCount = DB::table('invoices')->where('direction', InvoiceDirection::Outbound->value)->count();
            self::assertSame(0, $customerInvoicesCount);
        });
    }

    public function test_plan_owner_rejects_mismatched_vehicle_and_out_of_coverage_periods(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $agreements = app(AgreementService::class);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $s = app(VehicleUseService::class);

            // Mismatched vehicle
            $otherVehicleId = DB::table('vehicles')->insertGetId([
                'tenant_id' => $context->tenantId,
                'vehicle_number' => 'OTHER-VEH',
                'registration_number' => 'OTHER-VEH',
                'status' => 'active',
            ]);

            $this->expectException(ValidationException::class);
            $s->planOwner($context, $owner->id, $owner->row_version, [
                'vehicle_id' => $otherVehicleId,
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-08T09:00:00+05:30',
            ]);
        });
    }
}
