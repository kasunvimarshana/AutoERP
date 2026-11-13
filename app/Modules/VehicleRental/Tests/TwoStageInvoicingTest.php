<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Invoice\Models\Invoice;
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
}
