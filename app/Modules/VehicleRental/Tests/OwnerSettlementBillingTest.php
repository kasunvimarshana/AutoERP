<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Models\Invoice;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementAdjustmentCategory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalExpenseAllocation;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\OwnerSettlementBillingService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\TestCase;

final class OwnerSettlementBillingTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_owner_settlement_billing_generates_ap_bill_with_wht_deduction(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '50000.000000'];
            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            // Record a posted base charge for the lessor
            $baseCharge = new OwnerBaseCharge();
            $baseCharge->tenant_id = $context->tenantId;
            $baseCharge->organization_unit_id = $context->organizationUnitId;
            $baseCharge->agreement_id = $ownerAg->id;
            $baseCharge->period_from = '2026-09-01';
            $baseCharge->period_until = '2026-09-30';
            $baseCharge->amount = '50000.000000';
            $baseCharge->calculation = ['rate' => '50000.000000'];
            $baseCharge->actor_id = $context->actorId;
            $baseCharge->save();

            $settlementService = app(OwnerSettlementBillingService::class);
            $bill = $settlementService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => '10.000000',
                'invoice_date' => '2026-09-30',
            ]);

            $this->assertNotNull($bill);
            $this->assertSame(InvoiceDirection::Inbound, $bill->direction);
            $this->assertSame($ownerAg->supplier_id, $bill->party_id);

            // Gross is 50,000, 10% WHT discount is 5,000, Net Payable is 45,000
            $this->assertEqualsWithDelta(45000.0, (float) $bill->grand_total, 0.01);
            $this->assertEqualsWithDelta(5000.0, (float) $bill->discount_total, 0.01);
        });
    }

    public function test_owner_settlement_billing_deducts_maintenance_expense_chargebacks(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '50000.000000'];
            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            // Base charge of 50,000
            $baseCharge = new OwnerBaseCharge();
            $baseCharge->tenant_id = $context->tenantId;
            $baseCharge->organization_unit_id = $context->organizationUnitId;
            $baseCharge->agreement_id = $ownerAg->id;
            $baseCharge->period_from = '2026-09-01';
            $baseCharge->period_until = '2026-09-30';
            $baseCharge->amount = '50000.000000';
            $baseCharge->calculation = ['rate' => '50000.000000'];
            $baseCharge->actor_id = $context->actorId;
            $baseCharge->save();

            // Record an approved expense allocation for vehicle repair of 8,000
            $expense = new RentalExpense();
            $expense->tenant_id = $context->tenantId;
            $expense->organization_unit_id = $context->organizationUnitId;
            $expense->expense_number = 'EXP-TEST-001';
            $expense->expense_type = RentalExpenseType::Maintenance;
            $expense->status = RentalExpenseStatus::Approved;
            $expense->vehicle_id = $ownerData['vehicle_id'];
            $expense->vehicle_label_snapshot = 'CAR-01';
            $expense->fingerprint = 'FP-TEST-001';
            $expense->expense_date = '2026-09-10';
            $expense->currency_id = $ownerAg->currency_id;
            $expense->currency_code_snapshot = 'USD';
            $expense->gross_amount = '8000.000000';
            $expense->tax_amount = '0.000000';
            $expense->net_amount = '8000.000000';
            $expense->description = 'Brake overhaul';
            $expense->created_by = $context->actorId;
            $expense->save();

            $alloc = new RentalExpenseAllocation();
            $alloc->tenant_id = $context->tenantId;
            $alloc->organization_unit_id = $context->organizationUnitId;
            $alloc->expense_id = $expense->id;
            $alloc->sequence = 1;
            $alloc->allocation_type = RentalExpenseAllocationType::OwnerDeduction;
            $alloc->target_owner_agreement_id = $ownerAg->id;
            $alloc->net_amount = '8000.000000';
            $alloc->total_amount = '8000.000000';
            $alloc->status = RentalExpenseAllocationStatus::Confirmed;
            $alloc->save();

            $settlementService = app(OwnerSettlementBillingService::class);
            $bill = $settlementService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => '10.000000',
                'invoice_date' => '2026-09-30',
            ]);

            // Gross is 50,000. WHT (10%) = 5,000. Maintenance = 8,000. Total deduction = 13,000.
            // Net Payable = 37,000.
            $this->assertEqualsWithDelta(37000.0, (float) $bill->grand_total, 0.01);
            $this->assertEqualsWithDelta(13000.0, (float) $bill->discount_total, 0.01);

            // Verify allocation was linked to the generated bill
            $alloc->refresh();
            $this->assertSame($bill->id, $alloc->invoice_id);
        });
    }

    public function test_owner_settlement_billing_defaults_to_zero_wht_when_unspecified(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '50000.000000'];
            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $baseCharge = new OwnerBaseCharge();
            $baseCharge->tenant_id = $context->tenantId;
            $baseCharge->organization_unit_id = $context->organizationUnitId;
            $baseCharge->agreement_id = $ownerAg->id;
            $baseCharge->period_from = '2026-09-01';
            $baseCharge->period_until = '2026-09-30';
            $baseCharge->amount = '50000.000000';
            $baseCharge->calculation = ['rate' => '50000.000000'];
            $baseCharge->actor_id = $context->actorId;
            $baseCharge->save();

            $settlementService = app(OwnerSettlementBillingService::class);
            $bill = $settlementService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                // Notice: wht_rate is intentionally omitted
                'invoice_date' => '2026-09-30',
            ]);

            $this->assertNotNull($bill);
            // Gross is 50,000, 0% WHT discount is 0, Net Payable is full 50,000
            $this->assertEqualsWithDelta(50000.0, (float) $bill->grand_total, 0.01);
            $this->assertEqualsWithDelta(0.0, (float) $bill->discount_total, 0.01);
        });
    }

    public function test_owner_settlement_posting_plan_balanced_when_maintenance_equals_or_exceeds_gross(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '50000.000000'];
            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $baseCharge = new OwnerBaseCharge();
            $baseCharge->tenant_id = $context->tenantId;
            $baseCharge->organization_unit_id = $context->organizationUnitId;
            $baseCharge->agreement_id = $ownerAg->id;
            $baseCharge->period_from = '2026-09-01';
            $baseCharge->period_until = '2026-09-30';
            $baseCharge->amount = '50000.000000';
            $baseCharge->calculation = ['rate' => '50000.000000'];
            $baseCharge->actor_id = $context->actorId;
            $baseCharge->save();

            // Maintenance deduction of 60,000 (exceeds 50,000 gross - 5,000 WHT = 45,000 available)
            $expense = new RentalExpense();
            $expense->tenant_id = $context->tenantId;
            $expense->organization_unit_id = $context->organizationUnitId;
            $expense->expense_number = 'EXP-TEST-OVER';
            $expense->expense_type = RentalExpenseType::Maintenance;
            $expense->status = RentalExpenseStatus::Approved;
            $expense->vehicle_id = $ownerData['vehicle_id'];
            $expense->vehicle_label_snapshot = 'CAR-01';
            $expense->fingerprint = 'FP-TEST-OVER';
            $expense->expense_date = '2026-09-10';
            $expense->currency_id = $ownerAg->currency_id;
            $expense->currency_code_snapshot = 'USD';
            $expense->gross_amount = '60000.000000';
            $expense->tax_amount = '0.000000';
            $expense->net_amount = '60000.000000';
            $expense->description = 'Major engine overhaul';
            $expense->created_by = $context->actorId;
            $expense->save();

            $alloc = new RentalExpenseAllocation();
            $alloc->tenant_id = $context->tenantId;
            $alloc->organization_unit_id = $context->organizationUnitId;
            $alloc->expense_id = $expense->id;
            $alloc->sequence = 1;
            $alloc->allocation_type = RentalExpenseAllocationType::OwnerDeduction;
            $alloc->target_owner_agreement_id = $ownerAg->id;
            $alloc->net_amount = '60000.000000';
            $alloc->total_amount = '60000.000000';
            $alloc->status = RentalExpenseAllocationStatus::Confirmed;
            $alloc->save();

            $settlementService = app(OwnerSettlementBillingService::class);
            $bill = $settlementService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => '10.000000',
                'invoice_date' => '2026-09-30',
            ]);

            $this->assertNotNull($bill);
            // Net Payable must be exactly 0.00 (not negative!)
            $this->assertEqualsWithDelta(0.0, (float) $bill->grand_total, 0.01);

            // Maintenance capped to 45,000 + WHT 5,000 = 50,000 discount total
            $this->assertEqualsWithDelta(50000.0, (float) $bill->discount_total, 0.01);

            // Verify posting lines: Debit RentalExpense (50,000) = Credit WHT (5,000) + Credit RentalExpense (45,000)
            $lines = $bill->postingPlan->lines;
            $this->assertCount(3, $lines); // No zero-side payable line!
            $debits = collect($lines)->sum(fn ($l) => (float) (is_array($l) ? ($l['debit'] ?? 0) : $l->debit));
            $credits = collect($lines)->sum(fn ($l) => (float) (is_array($l) ? ($l['credit'] ?? 0) : $l->credit));
            $this->assertEqualsWithDelta(50000.0, $debits, 0.01);
            $this->assertEqualsWithDelta(50000.0, $credits, 0.01);
        });
    }

    public function test_owner_settlement_finalizes_unbilled_owner_billing_cycles(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '40000.000000'];
            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            // Create an unbilled owner BillingCycle for September
            $cycle = new BillingCycle();
            $cycle->tenant_id = $context->tenantId;
            $cycle->organization_unit_id = $context->organizationUnitId;
            $cycle->cycle_number = 'BC-OWNER-SEP';
            $cycle->owner_agreement_id = $ownerAg->id;
            $cycle->period_from = '2026-09-01';
            $cycle->period_until = '2026-09-30';
            $cycle->status = BillingCycleStatus::Draft;
            $cycle->base_rent_amount = '40000.000000';
            $cycle->excess_km_amount = '2000.000000';
            $cycle->overtime_amount = '1000.000000';
            $cycle->net_subtotal = '43000.000000';
            $cycle->total_billed_amount = '43000.000000';
            $cycle->currency_id = $ownerAg->currency_id;
            $cycle->exchange_rate = '1.000000';
            $cycle->row_version = AgreementFields::INITIAL_VERSION;
            $cycle->save();

            $settlementService = app(OwnerSettlementBillingService::class);
            $bill = $settlementService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => '0.000000',
                'invoice_date' => '2026-09-30',
            ]);

            $this->assertNotNull($bill);
            $this->assertEqualsWithDelta(43000.0, (float) $bill->grand_total, 0.01);

            // BillingCycle must now be finalized and linked to the bill
            $cycle->refresh();
            $this->assertSame(BillingCycleStatus::Finalized, $cycle->status);
            $this->assertSame($bill->id, $cycle->final_invoice_id);
        });
    }

    public function test_owner_settlement_integrates_agreement_adjustments(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '50000.000000'];
            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $baseCharge = new OwnerBaseCharge();
            $baseCharge->tenant_id = $context->tenantId;
            $baseCharge->organization_unit_id = $context->organizationUnitId;
            $baseCharge->agreement_id = $ownerAg->id;
            $baseCharge->period_from = '2026-09-01';
            $baseCharge->period_until = '2026-09-30';
            $baseCharge->amount = '50000.000000';
            $baseCharge->calculation = ['rate' => '50000.000000'];
            $baseCharge->actor_id = $context->actorId;
            $baseCharge->save();

            // Approved DebitNote adjustment: +3,000 lessor addition (e.g. bonus allowance)
            $debitAdj = new AgreementAdjustment();
            $debitAdj->tenant_id = $context->tenantId;
            $debitAdj->organization_unit_id = $context->organizationUnitId;
            $debitAdj->owner_agreement_id = $ownerAg->id;
            $debitAdj->adjustment_type = AgreementAdjustmentType::DebitNote;
            $debitAdj->category = AgreementAdjustmentCategory::Other;
            $debitAdj->status = AgreementAdjustmentStatus::Authorized;
            $debitAdj->amount = '3000.000000';
            $debitAdj->original_amount = '3000.000000';
            $debitAdj->tax_amount = '0.000000';
            $debitAdj->reason = 'Special cleaning allowance';
            $debitAdj->actor_id = $context->actorId;
            $debitAdj->authorized_by_user_id = $context->actorId;
            $debitAdj->authorized_at = now();
            $debitAdj->row_version = 1;
            $debitAdj->save();

            // Approved CreditNote adjustment: -5,000 lessor deduction (e.g. downtime compensation)
            $creditAdj = new AgreementAdjustment();
            $creditAdj->tenant_id = $context->tenantId;
            $creditAdj->organization_unit_id = $context->organizationUnitId;
            $creditAdj->owner_agreement_id = $ownerAg->id;
            $creditAdj->adjustment_type = AgreementAdjustmentType::CreditNote;
            $creditAdj->category = AgreementAdjustmentCategory::DowntimeCompensation;
            $creditAdj->status = AgreementAdjustmentStatus::Authorized;
            $creditAdj->amount = '5000.000000';
            $creditAdj->original_amount = '5000.000000';
            $creditAdj->tax_amount = '0.000000';
            $creditAdj->reason = 'Downtime rebate for garage maintenance';
            $creditAdj->actor_id = $context->actorId;
            $creditAdj->authorized_by_user_id = $context->actorId;
            $creditAdj->authorized_at = now();
            $creditAdj->row_version = 1;
            $creditAdj->save();

            $settlementService = app(OwnerSettlementBillingService::class);
            $bill = $settlementService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => '10.000000',
                'invoice_date' => '2026-09-30',
            ]);

            $this->assertNotNull($bill);

            // Gross = 50,000 (base) + 3,000 (debit adj) = 53,000
            // WHT (10%) = 5,300
            // Deduction = 5,000
            // Net Payable = 53,000 - 5,300 - 5,000 = 42,700
            $this->assertEqualsWithDelta(42700.0, (float) $bill->grand_total, 0.01);

            // Both adjustments must be transitioned to Applied and linked to the bill
            $debitAdj->refresh();
            $creditAdj->refresh();
            $this->assertSame(AgreementAdjustmentStatus::Applied, $debitAdj->status);
            $this->assertSame($bill->id, $debitAdj->invoice_id);
            $this->assertSame(AgreementAdjustmentStatus::Applied, $creditAdj->status);
            $this->assertSame($bill->id, $creditAdj->invoice_id);
        });
    }

    public function test_owner_settlement_date_format_mismatch_prevention(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '50000.000000'];
            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $baseCharge = new OwnerBaseCharge();
            $baseCharge->tenant_id = $context->tenantId;
            $baseCharge->organization_unit_id = $context->organizationUnitId;
            $baseCharge->agreement_id = $ownerAg->id;
            $baseCharge->period_from = '2026-09-01';
            $baseCharge->period_until = '2026-09-30';
            $baseCharge->amount = '50000.000000';
            $baseCharge->calculation = ['rate' => '50000.000000'];
            $baseCharge->actor_id = $context->actorId;
            $baseCharge->save();

            $settlementService = app(OwnerSettlementBillingService::class);
            $bill = $settlementService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => '0.000000',
                'invoice_date' => '2026-09-30',
            ]);

            $this->assertNotNull($bill);

            // Attempting to generate another overlapping bill for the same period must be rejected
            $this->expectException(\Illuminate\Validation\ValidationException::class);
            $settlementService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-15',
                'end_date' => '2026-10-15',
                'wht_rate' => '0.000000',
                'invoice_date' => '2026-10-15',
            ]);
        });
    }
}
