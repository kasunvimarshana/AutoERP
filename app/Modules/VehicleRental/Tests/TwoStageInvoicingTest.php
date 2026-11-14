<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BillingCycleItemType;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\BillingFinalizationMode;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\TwoStageInvoicingService;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class TwoStageInvoicingTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_two_stage_invoicing_initial_cycle_without_running_charts(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = [
                'base_rate' => '30000.000000',
            ];
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $agreements = app(AgreementService::class);
            $agreement = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agreement = $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $agreement->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
                'invoice_date' => '2026-09-01',
            ]);

            $this->assertSame(BillingCycleStatus::Stage1Generated, $cycle->status);
            $this->assertNotNull($cycle->initial_invoice_id);
            $this->assertEqualsWithDelta(30000.0, (float) $cycle->base_rent_amount, 0.01);

            $invoice = Invoice::query()->find($cycle->initial_invoice_id);
            $this->assertNotNull($invoice);
            $this->assertEqualsWithDelta(30000.0, (float) $invoice->grand_total, 0.01);
        });
    }

    public function test_two_stage_invoicing_attaches_running_charts_and_computes_excess_and_ot(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = [
                'base_rate' => '30000.000000',
                'included_km' => '1000.000000',
                'excess_km_rate' => '100.000000',
                'normal_ot_rate' => '1500.000000',
                'double_ot_rate' => '2000.000000',
                'night_out_rate' => '1000.000000',
            ];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, $customerAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-07T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Customer handover',
            ]);

            // Create and finalize a running chart
            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-TEST-001',
                'starts_at' => '2026-09-08T08:00:00+00:00',
                'ends_at' => '2026-09-08T20:00:00+00:00',
                'start_odometer' => '10000.000000',
                'end_odometer' => '11500.000000',
                'commercial_km' => '1500.000000',
                'normal_ot_minutes' => 120,
                'double_ot_minutes' => 60,
                'night_outs' => 1,
            ]);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize, ['reason' => 'Trip concluded']);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
                'invoice_date' => '2026-09-01',
            ]);

            // Attach running chart
            $cycle = $invoicing->attachRunningCharts($context, $cycle->id, [$chart->id], (int) $cycle->row_version);

            $this->assertSame(BillingCycleStatus::Stage2Attached, $cycle->status);
            $this->assertCount(1, $cycle->charts);

            // Excess KM: 1500 total - 1000 free = 500 excess * 100 = 50,000
            $this->assertEqualsWithDelta(50000.0, (float) $cycle->excess_km_amount, 0.01);
            // Normal OT: 120 min / 60 = 2 hr * 1500 = 3,000
            // Double OT: 60 min / 60 = 1 hr * 2000 = 2,000. Total OT = 5,000
            $this->assertEqualsWithDelta(5000.0, (float) $cycle->overtime_amount, 0.01);
            // Night out: 1 * 1000 = 1,000
            $this->assertEqualsWithDelta(1000.0, (float) $cycle->night_out_amount, 0.01);
        });
    }

    public function test_two_stage_invoicing_adds_and_removes_ad_hoc_items(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
            ]);

            // Add Driver Salary addition
            $item1 = $invoicing->addItem($context, $cycle->id, [
                'item_type' => 'addition',
                'category' => 'driver_salary',
                'description' => 'Driver monthly allowance',
                'quantity' => '1.000000',
                'unit_rate' => '15000.000000',
            ], (int) $cycle->row_version);

            $cycle->refresh();
            $this->assertEqualsWithDelta(15000.0, (float) $cycle->additions_amount, 0.01);
            $this->assertEqualsWithDelta(45000.0, (float) $cycle->net_subtotal, 0.01);

            // Add Downtime deduction
            $item2 = $invoicing->addItem($context, $cycle->id, [
                'item_type' => 'deduction',
                'category' => 'downtime_rebate',
                'description' => 'Downtime 2 days rebate',
                'quantity' => '2.000000',
                'unit_rate' => '1000.000000',
            ], (int) $cycle->row_version);

            $cycle->refresh();
            $this->assertEqualsWithDelta(2000.0, (float) $cycle->deductions_amount, 0.01);
            $this->assertEqualsWithDelta(43000.0, (float) $cycle->net_subtotal, 0.01);

            // Remove deduction
            $invoicing->removeItem($context, $cycle->id, $item2->id, (int) $cycle->row_version);
            $cycle->refresh();
            $this->assertEqualsWithDelta(0.0, (float) $cycle->deductions_amount, 0.01);
            $this->assertEqualsWithDelta(45000.0, (float) $cycle->net_subtotal, 0.01);
        });
    }

    public function test_two_stage_invoicing_finalizes_in_mode_a_supplementary_invoice(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
                'finalization_mode' => 'supplementary_invoice',
            ]);

            // Add addition of 10,000
            $invoicing->addItem($context, $cycle->id, [
                'item_type' => 'addition',
                'category' => 'fuel_charge',
                'description' => 'Fuel top-up surcharge',
                'quantity' => '1.000000',
                'unit_rate' => '10000.000000',
            ], (int) $cycle->row_version);

            $cycle->refresh();

            // Finalize with Mode A
            $finalized = $invoicing->finalize($context, $cycle->id, [
                'finalization_mode' => BillingFinalizationMode::SupplementaryInvoice->value,
                'invoice_date' => '2026-09-30',
            ], (int) $cycle->row_version);

            $this->assertSame(BillingCycleStatus::Finalized, $finalized->status);
            $this->assertNotNull($finalized->initial_invoice_id);
            $this->assertNotNull($finalized->supplementary_invoice_id);

            $suppInvoice = Invoice::query()->find($finalized->supplementary_invoice_id);
            $this->assertNotNull($suppInvoice);
            // Supplementary invoice bills exactly the delta of 10,000
            $this->assertEqualsWithDelta(10000.0, (float) $suppInvoice->grand_total, 0.01);
        });
    }

    public function test_two_stage_invoicing_finalizes_in_mode_b_consolidated_revision(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
                'invoice_date' => '2026-09-01',
                'finalization_mode' => 'consolidated_revision',
            ]);

            // Add addition of 5,000
            $invoicing->addItem($context, $cycle->id, [
                'item_type' => 'addition',
                'category' => 'toll_charge',
                'description' => 'Highway tolls reimbursed',
                'quantity' => '1.000000',
                'unit_rate' => '5000.000000',
            ], (int) $cycle->row_version);

            $cycle->refresh();

            // Finalize with Mode B
            $finalized = $invoicing->finalize($context, $cycle->id, [
                'finalization_mode' => BillingFinalizationMode::ConsolidatedRevision->value,
                'invoice_date' => '2026-09-30',
            ], (int) $cycle->row_version);

            $this->assertSame(BillingCycleStatus::Finalized, $finalized->status);
            $this->assertNotNull($finalized->initial_invoice_id);
            $this->assertNotNull($finalized->final_invoice_id);

            // Verify a Credit Note adjustment was generated for initial invoice
            $adj = AgreementAdjustment::query()->where('customer_agreement_id', $customerAg->id)->first();
            $this->assertNotNull($adj);
            $this->assertSame('credit_note', $adj->adjustment_type->value);
            $this->assertEqualsWithDelta(30000.0, (float) $adj->amount, 0.01);

            // Consolidated invoice bills total of 35,000
            $consolInvoice = Invoice::query()->find($finalized->final_invoice_id);
            $this->assertNotNull($consolInvoice);
            $this->assertEqualsWithDelta(35000.0, (float) $consolInvoice->grand_total, 0.01);
        });
    }

    public function test_two_stage_invoicing_eligible_charts_detection_and_conflict_prevention(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();
        $cycleId = null;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData, &$cycleId): void {
            $customerData['starts_on'] = '2026-08-01';
            $customerData['agreed_on'] = '2026-08-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-08-01';
            $ownerData['agreed_on'] = '2026-08-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, $customerAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-01T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Handover',
            ]);

            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-ELIGIBLE-01',
                'starts_at' => '2026-09-10T08:00:00+00:00',
                'ends_at' => '2026-09-10T18:00:00+00:00',
                'start_odometer' => '10000.000000',
                'end_odometer' => '10150.000000',
                'commercial_km' => '150.000000',
            ]);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize, ['reason' => 'Trip complete']);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => false,
            ]);
            $cycleId = $cycle->id;

            // Query candidates before attaching
            $candidates = $invoicing->getCandidateRunningCharts($context, $cycle->id);
            $this->assertCount(1, $candidates);
            $this->assertSame($chart->id, $candidates[0]['id']);
            $this->assertFalse($candidates[0]['is_attached']);
            $this->assertFalse($candidates[0]['is_already_finalized']);

            // Attach chart
            $invoicing->attachRunningCharts($context, $cycle->id, [$chart->id], (int) $cycle->row_version);

            // Candidate query now reports already attached
            $cycle->refresh();
            $candidatesAfter = $invoicing->getCandidateRunningCharts($context, $cycle->id);
            $this->assertCount(1, $candidatesAfter);
            $this->assertTrue($candidatesAfter[0]['is_attached']);
        });

        // Verify HTTP API returns candidate running charts
        $resp = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->getJson("/api/v1/vehicle-rental/billing-cycles/{$cycleId}/eligible-charts");
        $resp->assertOk();
        $resp->assertJsonFragment(['is_attached' => true]);
    }

    public function test_two_stage_invoicing_preview_computes_vehicle_and_driver_attributions(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();
        $cycleId = null;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData, &$cycleId): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = [
                'base_rate' => '30000.000000',
                'included_km' => '500.000000',
                'excess_km_rate' => '100.000000',
                'normal_ot_rate' => '1500.000000',
                'double_ot_rate' => '2000.000000',
                'night_out_rate' => '1000.000000',
            ];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $driverId = DB::table('hr_employees')->insertGetId([
                'tenant_id' => $context->tenantId,
                'employee_number' => 'DRV-PREVIEW-01',
                'first_name' => 'Michael',
                'display_name' => 'Michael Scott',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, $customerAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-01T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Handover',
            ]);

            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-PREV-01',
                'starts_at' => '2026-09-12T08:00:00+00:00',
                'ends_at' => '2026-09-12T22:00:00+00:00',
                'start_odometer' => '10000.000000',
                'end_odometer' => '10250.000000',
                'commercial_km' => '250.000000',
                'normal_ot_minutes' => 120,
                'double_ot_minutes' => 60,
                'night_outs' => 2,
                'driver_identity_source' => 'employee',
                'driver_employee_id' => $driverId,
                'driver_name_snapshot' => 'Michael Scott',
            ]);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize, ['reason' => 'Trip complete']);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
            ]);
            $cycleId = $cycle->id;

            $invoicing->attachRunningCharts($context, $cycle->id, [$chart->id], (int) $cycle->row_version);

            // Preview final stage
            $preview = $invoicing->previewFinalStage($context, $cycle->id);

            $this->assertNotEmpty($preview['vehicle_attributions']);
            $this->assertEqualsWithDelta(250.0, (float) $preview['vehicle_attributions'][0]['total_km'], 0.01);
            $this->assertSame(120, $preview['vehicle_attributions'][0]['normal_ot_minutes']);
            $this->assertSame(60, $preview['vehicle_attributions'][0]['double_ot_minutes']);

            $this->assertNotEmpty($preview['driver_attributions']);
            $drv = $preview['driver_attributions'][0];
            $this->assertSame('Michael Scott', $drv['driver_name']);
            $this->assertEqualsWithDelta(250.0, (float) $drv['total_km'], 0.01);
            // Normal OT: 120 mins = 2 hrs * 1500 = 3000
            $this->assertEqualsWithDelta(3000.0, (float) $drv['normal_ot_amount'], 0.01);
            // Double OT: 60 mins = 1 hr * 2000 = 2000
            $this->assertEqualsWithDelta(2000.0, (float) $drv['double_ot_amount'], 0.01);
            // Night out: 2 * 1000 = 2000
            $this->assertEqualsWithDelta(2000.0, (float) $drv['night_out_amount'], 0.01);
            // Total earnings: 3000 + 2000 + 2000 = 7000
            $this->assertEqualsWithDelta(7000.0, (float) $drv['total_earnings'], 0.01);
        });

        // Verify HTTP preview endpoint returns data
        $httpResp = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->getJson("/api/v1/vehicle-rental/billing-cycles/{$cycleId}/preview");
        $httpResp->assertOk();
        $httpResp->assertJsonPath('data.driver_attributions.0.driver_name', 'Michael Scott');
    }

    public function test_two_stage_invoicing_statement_export_views(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();
        $cycleId = null;
        $ref = null;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$cycleId, &$ref): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '45000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);
            $ref = $customerAg->reference;

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
            ]);
            $cycleId = $cycle->id;

            // Add an item
            $invoicing->addItem($context, $cycle->id, [
                'item_type' => 'addition',
                'category' => 'fuel_charge',
                'description' => 'Airport fuel top up',
                'quantity' => '1.000000',
                'unit_rate' => '8500.000000',
            ], (int) $cycle->row_version);
        });

        // Test Print HTML Export
        $printResp = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->get("/api/v1/vehicle-rental/billing-cycles/{$cycleId}/export/print");
        $printResp->assertOk();
        $printResp->assertSee('Vehicle Rental Billing Statement', false);
        $printResp->assertSee($ref, false);
        $printResp->assertSee('Airport fuel top up', false);

        // Test Browser HTML Export
        $htmlResp = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->get("/api/v1/vehicle-rental/billing-cycles/{$cycleId}/export/html");
        $htmlResp->assertOk();
        $htmlResp->assertSee('Vehicle Rental Billing Statement', false);
        $htmlResp->assertSee('Total Billed Net Amount:', false);
    }

    public function test_two_stage_invoicing_mode_b_reallocates_existing_payments(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '50000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
                'invoice_date' => '2026-09-01',
                'finalization_mode' => 'consolidated_revision',
            ]);

            $initialInvoice = Invoice::query()->find($cycle->initial_invoice_id);
            $this->assertNotNull($initialInvoice);

            // Seed finance posting profile and payment method
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedCustomerPaymentProfiles($context->tenantId, $context->organizationUnitId);
            $paymentMethodId = (int) DB::table('payment_methods')->insertGetId([
                'tenant_id' => $context->tenantId,
                'scope_key' => 'tenant:'.$context->tenantId,
                'code' => 'TEST-CASH-ALLOC',
                'name' => 'Cash Allocation Test',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Transition initial invoice to Approved then Posted so it can receive payments
            $invoiceStatuses = app(InvoiceStatusService::class);
            $initialInvoice = $invoiceStatuses->transition($initialInvoice, InvoiceStatus::Approved, $context->actorId);
            $initialInvoice = $invoiceStatuses->transition($initialInvoice, InvoiceStatus::Posted, $context->actorId);

            // Create customer payment of 20,000 against initial invoice
            $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
                tenantId: $context->tenantId,
                paymentType: PaymentType::CustomerReceipt,
                direction: PaymentDirection::Inbound,
                paymentDate: '2026-09-10',
                organizationUnitId: $context->organizationUnitId,
                partyType: 'customer',
                partyId: (int) $customerAg->customer_id,
                currencyId: (int) $customerAg->currency_id,
                lines: [new PaymentLineData(
                    amount: '20000.000000',
                    paymentMethodId: $paymentMethodId,
                )],
            ));

            $lifecycle = app(PaymentDocumentLifecycleService::class);
            $payment = $lifecycle->submit($payment, (int) $payment->row_version);
            $payment = $lifecycle->approve($payment, (int) $payment->row_version);
            $payment = app(PaymentPostingService::class)->post($payment, (int) $payment->row_version);

            // Allocate 20,000 to the initial provisional invoice
            $payment = app(PaymentAllocationService::class)->allocate(
                $payment,
                [new PaymentAllocationData(
                    invoiceId: $initialInvoice->id,
                    allocatedAmount: '20000.000000',
                    allocationDate: '2026-09-10',
                    allocationMethod: 'specific_invoice',
                )],
                (int) $payment->row_version,
                $context->actorId,
            );

            // Verify active allocation exists on initial invoice
            $initialAlloc = PaymentAllocation::query()
                ->where('invoice_id', $initialInvoice->id)
                ->where('status', AllocationStatus::Active->value)
                ->first();
            $this->assertNotNull($initialAlloc);
            $this->assertEqualsWithDelta(20000.0, (float) $initialAlloc->allocated_amount, 0.01);

            // Add an addition of 10,000 to the cycle
            $invoicing->addItem($context, $cycle->id, [
                'item_type' => 'addition',
                'category' => 'fuel_charge',
                'description' => 'Supplementary fuel cost',
                'quantity' => '1.000000',
                'unit_rate' => '10000.000000',
            ], (int) $cycle->row_version);

            $cycle->refresh();

            // Finalize in Mode B (Consolidated Revision)
            $finalized = $invoicing->finalize($context, $cycle->id, [
                'finalization_mode' => BillingFinalizationMode::ConsolidatedRevision->value,
                'invoice_date' => '2026-09-30',
            ], (int) $cycle->row_version);

            $this->assertSame(BillingCycleStatus::Finalized, $finalized->status);
            $this->assertNotNull($finalized->final_invoice_id);

            // Verify initial allocation is now Reversed
            $initialAlloc->refresh();
            $this->assertSame(AllocationStatus::Reversed, $initialAlloc->status);

            // Verify new allocation is Active on the consolidated invoice
            $finalAlloc = PaymentAllocation::query()
                ->where('invoice_id', $finalized->final_invoice_id)
                ->where('status', AllocationStatus::Active->value)
                ->first();
            $this->assertNotNull($finalAlloc);
            $this->assertEqualsWithDelta(20000.0, (float) $finalAlloc->allocated_amount, 0.01);

            // Verify cycle paid amount tracks the transferred payment
            $finalized->refresh();
            $this->assertEqualsWithDelta(20000.0, (float) $finalized->total_paid_amount, 0.01);

            // Verify Credit Note was generated for the 50,000 initial invoice
            $adj = AgreementAdjustment::query()->where('customer_agreement_id', $customerAg->id)->first();
            $this->assertNotNull($adj);
            $this->assertSame('credit_note', $adj->adjustment_type->value);
            $this->assertEqualsWithDelta(50000.0, (float) $adj->amount, 0.01);

            // Consolidated invoice is 60,000 (50k base + 10k fuel)
            $consol = Invoice::query()->find($finalized->final_invoice_id);
            $this->assertNotNull($consol);
            $this->assertEqualsWithDelta(60000.0, (float) $consol->grand_total, 0.01);
        });
    }

    public function test_billing_cycle_number_is_sequential_and_atomic(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle1 = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-15',
            ]);
            $cycle2 = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-16',
                'period_until' => '2026-09-30',
            ]);

            $this->assertStringStartsWith('VRBC-', $cycle1->cycle_number);
            $this->assertStringStartsWith('VRBC-', $cycle2->cycle_number);
            $this->assertNotEquals($cycle1->cycle_number, $cycle2->cycle_number);
        });
    }

    public function test_unconfigured_ot_and_night_out_rates_default_to_zero(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            // Notice: NO normal_ot_rate, double_ot_rate, triple_ot_rate, or night_out_rate
            $customerData['terms'] = [
                'base_rate' => '30000.000000',
                'included_km' => '1000.000000',
            ];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, (int) $customerAg->row_version, [
                'vehicle_id' => $ownerAg->vehicle_id,
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-01T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, (int) $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Customer handover',
            ]);

            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-TEST-UNCONFIG',
                'starts_at' => '2026-09-02T08:00:00+00:00',
                'ends_at' => '2026-09-02T20:00:00+00:00',
                'start_odometer' => '10000.000000',
                'end_odometer' => '10500.000000',
                'commercial_km' => '500.000000',
                'normal_ot_minutes' => 120,
                'double_ot_minutes' => 60,
                'triple_ot_minutes' => 30,
                'night_outs' => 2,
            ]);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize, ['reason' => 'Trip complete']);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
            ]);

            $cycle = $invoicing->attachRunningCharts($context, $cycle->id, [$chart->id], (int) $cycle->row_version);

            // Undefined rates MUST resolve to 0.000000 automatic charge
            $this->assertEqualsWithDelta(0.0, (float) $cycle->overtime_amount, 0.0001);
            $this->assertEqualsWithDelta(0.0, (float) $cycle->night_out_amount, 0.0001);
        });
    }

    public function test_two_stage_invoicing_computes_triple_overtime(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = [
                'base_rate' => '30000.000000',
                'included_km' => '1000.000000',
                'excess_km_rate' => '100.000000',
                'normal_ot_rate' => '1500.000000',
                'double_ot_rate' => '2000.000000',
                'triple_ot_rate' => '3000.000000',
                'night_out_rate' => '1000.000000',
            ];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, (int) $customerAg->row_version, [
                'vehicle_id' => $ownerAg->vehicle_id,
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-01T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, (int) $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Customer handover',
            ]);

            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-TEST-TRIPLE-OT',
                'starts_at' => '2026-09-02T08:00:00+00:00',
                'ends_at' => '2026-09-02T20:00:00+00:00',
                'start_odometer' => '10000.000000',
                'end_odometer' => '10500.000000',
                'commercial_km' => '500.000000',
                'normal_ot_minutes' => 60,   // 1 hr * 1500 = 1,500
                'double_ot_minutes' => 60,   // 1 hr * 2000 = 2,000
                'triple_ot_minutes' => 60,   // 1 hr * 3000 = 3,000. Total OT = 6,500
                'night_outs' => 1,
            ]);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize, ['reason' => 'Trip complete']);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
            ]);

            $cycle = $invoicing->attachRunningCharts($context, $cycle->id, [$chart->id], (int) $cycle->row_version);

            // Total OT = 1500 + 2000 + 3000 = 6,500
            $this->assertEqualsWithDelta(6500.0, (float) $cycle->overtime_amount, 0.01);

            // Preview attributions
            $preview = $invoicing->previewFinalStage($context, $cycle->id);
            $this->assertNotEmpty($preview['vehicle_attributions']);
            $this->assertEqualsWithDelta(6500.0, (float) $preview['vehicle_attributions'][0]['allocated_ot_amount'], 0.01);
            $this->assertSame(60, $preview['vehicle_attributions'][0]['triple_ot_minutes']);
        });
    }

    public function test_non_running_chart_multi_vehicle_custody_proration(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = [
                'base_rate' => '30000.000000',
            ];
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, (int) $customerAg->row_version, AgreementAction::Activate);

            // Vehicle 1
            $ownerAg1 = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg1 = $agreements->change(AgreementKind::Owner, $context, $ownerAg1->id, (int) $ownerAg1->row_version, AgreementAction::Activate);

            // Vehicle 2
            $vehicle2Id = DB::table('vehicles')->insertGetId([
                'tenant_id' => $context->tenantId,
                'vehicle_number' => 'VEH-002',
                'registration_number' => 'REG-002',
                'status' => 'active',
            ]);
            $ownerData2 = array_replace($ownerData, [
                'reference' => 'AG-OWNER-2',
                'vehicle_id' => $vehicle2Id,
            ]);
            $ownerAg2 = $agreements->create(AgreementKind::Owner, $context, $ownerData2);
            $ownerAg2 = $agreements->change(AgreementKind::Owner, $context, $ownerAg2->id, (int) $ownerAg2->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);

            // Vehicle 1 used for 10 days: 2026-09-01 to 2026-09-10
            $use1 = $uses->plan($context, $customerAg->id, (int) $customerAg->row_version, [
                'vehicle_id' => $ownerAg1->vehicle_id,
                'owner_agreement_id' => $ownerAg1->id,
                'starts_at' => '2026-09-01T00:00:00+00:00',
                'ends_at' => '2026-09-10T23:59:59+00:00',
            ]);
            $use1 = $uses->transition($context, $use1->id, (int) $use1->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T00:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'First vehicle handover',
            ]);

            // Vehicle 2 used for 20 days: 2026-09-11 to 2026-09-30
            $customerAg = $customerAg->fresh();
            $use2 = $uses->plan($context, $customerAg->id, (int) $customerAg->row_version, [
                'vehicle_id' => $vehicle2Id,
                'owner_agreement_id' => $ownerAg2->id,
                'starts_at' => '2026-09-11T00:00:00+00:00',
                'ends_at' => '2026-09-30T23:59:59+00:00',
            ]);
            $use2 = $uses->transition($context, $use2->id, (int) $use2->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-11T00:00:00+00:00',
                'odometer' => '50000.000000',
                'reason' => 'Second vehicle replacement handover',
            ]);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => false,
            ]);

            // No running charts attached -> Non-running chart mode
            $preview = $invoicing->previewFinalStage($context, $cycle->id);

            $this->assertFalse($preview['has_running_charts']);
            $this->assertCount(2, $preview['vehicle_attributions']);

            $attr1 = collect($preview['vehicle_attributions'])->firstWhere('vehicle_id', $ownerAg1->vehicle_id);
            $attr2 = collect($preview['vehicle_attributions'])->firstWhere('vehicle_id', $vehicle2Id);

            $this->assertNotNull($attr1);
            $this->assertNotNull($attr2);

            $this->assertSame(10, $attr1['custody_days']);
            $this->assertSame(20, $attr2['custody_days']);

            // 10/30 * 30000 = 10000, 20/30 * 30000 = 20000
            $this->assertEqualsWithDelta(10000.0, (float) $attr1['allocated_base_rent'], 0.01);
            $this->assertEqualsWithDelta(20000.0, (float) $attr2['allocated_base_rent'], 0.01);

            $totalAllocatedRent = (float) $attr1['allocated_base_rent'] + (float) $attr2['allocated_base_rent'];
            $this->assertEqualsWithDelta(30000.0, $totalAllocatedRent, 0.01);
        });
    }

    public function test_item_attribution_to_specific_vehicle_and_driver(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = [
                'base_rate' => '30000.000000',
            ];
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, (int) $customerAg->row_version, AgreementAction::Activate);

            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, (int) $ownerAg->row_version, AgreementAction::Activate);

            $driverId = DB::table('hr_employees')->insertGetId([
                'tenant_id' => $context->tenantId,
                'employee_number' => 'DRV-ATTR-01',
                'first_name' => 'Dwight',
                'display_name' => 'Dwight Schrute',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, (int) $customerAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-01T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, (int) $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Handover',
            ]);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
            ]);

            // Add line items with specific vehicle and driver attribution
            $invoicing->addItem($context, $cycle->id, [
                'item_type' => BillingCycleItemType::Addition->value,
                'category' => \Modules\VehicleRental\Enums\BillingCycleItemCategory::FuelCharge->value,
                'description' => 'Vehicle Fuel Surcharge',
                'quantity' => '1.000000',
                'unit_rate' => '4500.000000',
                'vehicle_id' => $ownerData['vehicle_id'],
            ], (int) $cycle->row_version);
            $cycle = $cycle->fresh();

            $invoicing->addItem($context, $cycle->id, [
                'item_type' => BillingCycleItemType::Addition->value,
                'category' => \Modules\VehicleRental\Enums\BillingCycleItemCategory::DriverAllowance->value,
                'description' => 'Driver Outstation Allowance',
                'quantity' => '1.000000',
                'unit_rate' => '2500.000000',
                'driver_employee_id' => $driverId,
            ], (int) $cycle->row_version);
            $cycle = $cycle->fresh();

            $invoicing->addItem($context, $cycle->id, [
                'item_type' => BillingCycleItemType::Deduction->value,
                'category' => \Modules\VehicleRental\Enums\BillingCycleItemCategory::Other->value,
                'description' => 'Driver Late Penalty',
                'quantity' => '1.000000',
                'unit_rate' => '500.000000',
                'driver_employee_id' => $driverId,
            ], (int) $cycle->row_version);
            $cycle = $cycle->fresh();

            // Assert items table has stored attributes
            $items = $cycle->fresh(['items.vehicle', 'items.driver'])->items;
            $this->assertCount(3, $items);

            $fuelItem = $items->firstWhere('description', 'Vehicle Fuel Surcharge');
            $this->assertSame($ownerData['vehicle_id'], $fuelItem->vehicle_id);
            $this->assertNotNull($fuelItem->vehicle);
            $this->assertNull($fuelItem->driver_employee_id);

            $allowanceItem = $items->firstWhere('description', 'Driver Outstation Allowance');
            $this->assertSame($driverId, $allowanceItem->driver_employee_id);
            $this->assertNotNull($allowanceItem->driver);
            $this->assertNull($allowanceItem->vehicle_id);

            // Preview final stage and check attributions
            $preview = $invoicing->previewFinalStage($context, $cycle->id);

            // Vehicle attributions
            $vehAttr = collect($preview['vehicle_attributions'])->firstWhere('vehicle_id', $ownerData['vehicle_id']);
            $this->assertNotNull($vehAttr);
            $this->assertEqualsWithDelta(4500.0, (float) $vehAttr['attributed_additions'], 0.01);
            $this->assertEqualsWithDelta(0.0, (float) $vehAttr['attributed_deductions'], 0.01);

            // Driver attributions
            $drvAttr = collect($preview['driver_attributions'])->firstWhere('driver_employee_id', $driverId);
            $this->assertNotNull($drvAttr);
            $this->assertSame('Dwight Schrute', $drvAttr['driver_name']);
            $this->assertEqualsWithDelta(2500.0, (float) $drvAttr['attributed_additions'], 0.01);
            $this->assertEqualsWithDelta(500.0, (float) $drvAttr['attributed_deductions'], 0.01);
            $this->assertEqualsWithDelta(2000.0, (float) $drvAttr['total_earnings'], 0.01);

            // Check line items in preview
            $previewItems = collect($preview['line_items']);
            $fuelPreview = $previewItems->firstWhere('description', 'Vehicle Fuel Surcharge');
            $this->assertSame($ownerData['vehicle_id'], $fuelPreview['vehicle_id']);

            $drvPreview = $previewItems->firstWhere('description', 'Driver Outstation Allowance');
            $this->assertSame($driverId, $drvPreview['driver_employee_id']);
            $this->assertSame('Dwight Schrute', $drvPreview['driver_name']);
        });
    }

    public function test_draft_or_reversed_running_charts_cannot_be_attached_to_billing_cycle(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-08-01';
            $customerData['agreed_on'] = '2026-08-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-08-01';
            $ownerData['agreed_on'] = '2026-08-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, $customerAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-01T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Customer handover',
            ]);

            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-TEST-DRAFT',
                'starts_at' => '2026-09-05T08:00:00+00:00',
                'ends_at' => '2026-09-05T20:00:00+00:00',
                'start_odometer' => '10000.000000',
                'end_odometer' => '10200.000000',
                'commercial_km' => '200.000000',
            ]);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => false,
            ]);

            // Attempt to attach Draft running chart -> must fail
            try {
                $invoicing->attachRunningCharts($context, $cycle->id, [$chart->id], (int) $cycle->row_version);
                $this->fail('Attaching draft running chart must throw ValidationException');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('chart_ids', $e->errors());
            }

            // Finalize then reverse the running chart
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize, reason: 'Trip concluded');
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Reverse, reason: 'Data error correction');

            // Attempt to attach Reversed running chart -> must fail
            try {
                $invoicing->attachRunningCharts($context, $cycle->id, [$chart->id], (int) $cycle->row_version);
                $this->fail('Attaching reversed running chart must throw ValidationException');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('chart_ids', $e->errors());
            }
        });
    }

    public function test_cannot_create_overlapping_billing_cycles_for_same_agreement(): void
    {
        [$context, $customerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => false,
            ]);

            // Attempt overlapping cycle
            $this->expectException(ValidationException::class);
            $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-15',
                'period_until' => '2026-10-15',
                'issue_initial_invoice' => false,
            ]);
        });
    }

    public function test_mode_a_with_negative_delta_issues_credit_note(): void
    {
        [$context, $customerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
                'invoice_date' => '2026-09-01',
            ]);

            // Add a deduction (downtime rebate) of 5,000 so net subtotal = 25,000 (delta = -5,000)
            $invoicing->addItem($context, $cycle->id, [
                'item_type' => 'deduction',
                'category' => 'downtime_rebate',
                'description' => 'Vehicle breakdown rebate',
                'quantity' => '1.000000',
                'unit_rate' => '5000.000000',
            ], (int) $cycle->row_version);

            $cycle->refresh();

            // Finalize with Mode A
            $finalized = $invoicing->finalize($context, $cycle->id, [
                'finalization_mode' => BillingFinalizationMode::SupplementaryInvoice->value,
                'invoice_date' => '2026-09-30',
            ], (int) $cycle->row_version);

            $this->assertSame(BillingCycleStatus::Finalized, $finalized->status);
            $this->assertNotNull($finalized->supplementary_invoice_id);

            // Verify the supplementary invoice is a Credit Note for 5,000
            $creditInvoice = Invoice::query()->find($finalized->supplementary_invoice_id);
            $this->assertNotNull($creditInvoice);
            $this->assertEqualsWithDelta(5000.0, (float) $creditInvoice->grand_total, 0.01);
            $this->assertSame('credit', $creditInvoice->invoice_type->value);
            $this->assertEqualsWithDelta(25000.0, (float) $finalized->total_billed_amount, 0.01);
        });
    }

    public function test_mode_b_voids_approved_initial_invoice_directly(): void
    {
        [$context, $customerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
                'invoice_date' => '2026-09-01',
            ]);

            $initialInvoice = Invoice::query()->findOrFail($cycle->initial_invoice_id);
            $invoiceStatuses = app(InvoiceStatusService::class);
            // Transition initial invoice to Approved (unposted)
            $initialInvoice = $invoiceStatuses->transition($initialInvoice, InvoiceStatus::Approved, $context->actorId, 'Manager approved');
            $this->assertSame(InvoiceStatus::Approved, $initialInvoice->status);

            // Finalize with Mode B
            $finalized = $invoicing->finalize($context, $cycle->id, [
                'finalization_mode' => BillingFinalizationMode::ConsolidatedRevision->value,
                'invoice_date' => '2026-09-30',
            ], (int) $cycle->row_version);

            $this->assertSame(BillingCycleStatus::Finalized, $finalized->status);
            $initialInvoice->refresh();
            $this->assertSame(InvoiceStatus::Void, $initialInvoice->status);
            $this->assertNotNull($finalized->final_invoice_id);
        });
    }

    public function test_non_running_chart_preview_custody_boundaries_and_reconciliation(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-08-01';
            $customerData['agreed_on'] = '2026-08-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-08-01';
            $ownerData['agreed_on'] = '2026-08-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            // Use 1 for vehicle 1: in August only (ended before September)
            $uses = app(VehicleUseService::class);
            $use1 = $uses->plan($context, $customerAg->id, $customerAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-08-01T08:00:00+00:00',
                'ends_at' => '2026-08-20T18:00:00+00:00',
            ]);
            $use1 = $uses->transition($context, $use1->id, $use1->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-08-01T08:00:00+00:00',
                'odometer' => '1000.000000',
                'reason' => 'August handover',
            ]);
            $uses->transition($context, $use1->id, $use1->row_version, VehicleUseAction::ReturnVehicle, [
                'occurred_at' => '2026-08-20T18:00:00+00:00',
                'odometer' => '2000.000000',
                'reason' => 'August return',
            ]);

            // Create vehicle 2
            $vehicle2Id = DB::table('vehicles')->insertGetId([
                'tenant_id' => $context->tenantId,
                'vehicle_number' => 'V-SEP-002',
                'registration_number' => 'WP-CAD-9999',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $vehicle2Id,
                'owner_type' => 'company',
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => 'company_owned',
                'started_at' => '2026-08-01 00:00:00',
            ]);

            // Use 2 for vehicle 2: September full month
            $customerAg->refresh();
            $use2 = $uses->plan($context, $customerAg->id, $customerAg->row_version, [
                'vehicle_id' => $vehicle2Id,
                'starts_at' => '2026-09-01T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use2 = $uses->transition($context, $use2->id, $use2->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+00:00',
                'odometer' => '5000.000000',
                'reason' => 'September handover',
            ]);

            // Create BillingCycle for September without running charts
            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $customerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => false,
            ]);

            // Add excess KM and tolls to the cycle
            $cycle->excess_km_amount = '2500.000000';
            $cycle->tolls_amount = '500.000000';
            $cycle->net_subtotal = '33000.000000';
            $cycle->total_billed_amount = '33000.000000';
            $cycle->save();

            $preview = $invoicing->previewFinalStage($context, $cycle->id);

            // Vehicle 1 must NOT be in attributions because it had 0 custody in September
            $vehAttributions = $preview['vehicle_attributions'];
            $this->assertCount(1, $vehAttributions);
            $this->assertSame($vehicle2Id, (int) $vehAttributions[0]['vehicle_id']);
            $this->assertSame(30, (int) $vehAttributions[0]['custody_days']);
            $this->assertEqualsWithDelta(30000.0, (float) $vehAttributions[0]['allocated_base_rent'], 0.01);
            $this->assertEqualsWithDelta(2500.0, (float) $vehAttributions[0]['allocated_excess_km_amount'], 0.01);
            $this->assertEqualsWithDelta(500.0, (float) $vehAttributions[0]['allocated_tolls_amount'], 0.01);
            // Subtotal must reconcile with net_subtotal
            $this->assertEqualsWithDelta(33000.0, (float) $vehAttributions[0]['subtotal'], 0.01);
        });
    }
}
