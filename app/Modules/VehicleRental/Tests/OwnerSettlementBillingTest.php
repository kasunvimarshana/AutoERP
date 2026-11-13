<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Models\Invoice;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
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
}
