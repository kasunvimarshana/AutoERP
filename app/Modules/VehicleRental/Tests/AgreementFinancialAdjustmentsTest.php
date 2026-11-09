<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceBalanceService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Services\AgreementFinancialSummaryService;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalChargeDocuments;
use Modules\VehicleRental\Services\RentalExpenseService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

final class AgreementFinancialAdjustmentsTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    private const ROOT = '/api/v1/vehicle-rental';

    public function test_agreement_financial_summary_aggregates_financial_movements(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(\Modules\VehicleRental\Services\RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $documents = app(RentalChargeDocuments::class);

            // 1. Issue an adjustment Credit Note
            $creditNote = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $customerAg,
                'credit_note',
                '2500.000000',
                'Breakdown downtime rebate',
                [
                    'invoice_date' => '2026-09-15',
                    'exchange_rate' => '1.000000',
                    'expected_version' => $customerAg->row_version,
                ],
            );

            // 2. Issue an adjustment Debit Note
            $debitNote = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $customerAg,
                'debit_note',
                '5000.000000',
                'Damage repair recharge',
                [
                    'invoice_date' => '2026-09-16',
                    'exchange_rate' => '1.000000',
                    'expected_version' => $customerAg->row_version,
                ],
            );

            $summaryService = app(AgreementFinancialSummaryService::class);
            $result = $summaryService->generate(AgreementKind::Customer, $context, $customerAg->id);

            $this->assertSame($customerAg->reference, $result['agreement']['reference']);
            $this->assertCount(2, $result['documents']);

            // Summary checks
            $this->assertSame('2500.000000', $result['summary']['total_credit_notes']);
            $this->assertSame('2500.000000', $result['summary']['total_unallocated_credit']);
            $this->assertSame('5000.000000', $result['summary']['total_debit_notes']);
            $this->assertSame('5000.000000', $result['summary']['total_invoiced']);
            $this->assertSame('5000.000000', $result['summary']['total_remaining_balance']);
        });
    }

    public function test_financial_adjustment_api_endpoints_for_customer_and_owner(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(null, [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);

        $ownerData['reference'] = 'AG-OWN-TEST';

        // 1. Setup Customer and Owner agreements
        [$customerAgId, $customerVer, $ownerAgId, $ownerVer] = $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): array {
            $agreements = app(AgreementService::class);
            $c = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $c = $agreements->change(AgreementKind::Customer, $context, $c->id, $c->row_version, AgreementAction::Activate);

            $o = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $o = $agreements->change(AgreementKind::Owner, $context, $o->id, $o->row_version, AgreementAction::Activate);

            return [$c->id, $c->row_version, $o->id, $o->row_version];
        });

        // 2. Post Credit Note to customer agreement via HTTP
        $creditResp = $this->postJson(self::ROOT . '/customer/agreements/' . $customerAgId . '/financial-adjustments', [
            'expected_version' => $customerVer,
            'adjustment_type' => 'credit_note',
            'amount' => '3500.000000',
            'reason' => 'Customer rebate',
            'invoice_date' => '2026-09-12',
            'exchange_rate' => '1.000000',
        ])->assertCreated();

        $this->assertSame(InvoiceType::Credit->value, $creditResp->json('data.invoice_type'));
        $this->assertSame('3500.000000', $creditResp->json('data.grand_total'));

        // 3. Post Debit Note to owner agreement via HTTP
        $debitResp = $this->postJson(self::ROOT . '/owner/agreements/' . $ownerAgId . '/financial-adjustments', [
            'expected_version' => $ownerVer,
            'adjustment_type' => 'debit_note',
            'amount' => '4200.000000',
            'reason' => 'Owner maintenance chargeback',
            'invoice_date' => '2026-09-14',
            'exchange_rate' => '1.000000',
        ])->assertCreated();

        $this->assertSame(InvoiceType::Debit->value, $debitResp->json('data.invoice_type'));
        $this->assertSame('4200.000000', $debitResp->json('data.grand_total'));

        // 4. Get Financial Summary via HTTP
        $summaryResp = $this->getJson(self::ROOT . '/customer/agreements/' . $customerAgId . '/financial-summary')
            ->assertOk();

        $this->assertSame('3500.000000', $summaryResp->json('data.summary.total_credit_notes'));
        $this->assertCount(1, $summaryResp->json('data.documents'));
    }

    public function test_unlink_cancelled_invoice_from_expense_allocation(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(null, [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);

        [$allocId, $customerAgVer, $invoiceId] = $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): array {
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $service = app(RentalExpenseService::class);
            $expense = $service->create([
                'vehicle_id' => $ownerData['vehicle_id'],
                'expense_type' => RentalExpenseType::Toll->value,
                'expense_date' => '2026-09-18',
                'currency_id' => $ownerData['currency_id'],
                'net_amount' => '800.000000',
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $submitted = $service->transition($expense, RentalExpenseStatus::Submitted, $expense->row_version, $context->actorId);
            $approved = $service->transition($submitted, RentalExpenseStatus::Approved, $submitted->row_version, $context->actorId);
            $allocated = $service->transition($approved, RentalExpenseStatus::Allocated, $approved->row_version, $context->actorId, [
                [
                    'allocation_type' => RentalExpenseAllocationType::CustomerRecovery->value,
                    'target_customer_agreement_id' => $customerAg->id,
                    'net_amount' => '800.000000',
                ],
            ]);

            $alloc = $allocated->allocations->first();
            $invoice = $service->billAllocation($alloc, $context, [
                'expected_allocation_version' => $alloc->row_version,
                'expected_agreement_version' => $customerAg->row_version,
                'invoice_date' => '2026-09-19',
                'exchange_rate' => '1.000000',
            ]);

            return [$alloc->id, $customerAg->row_version, $invoice->id];
        });

        // 1. Try to unlink while invoice is active - should fail with 409
        $this->postJson(self::ROOT . '/expenses/allocations/' . $allocId . '/unlink-invoice')
            ->assertStatus(409);

        // 2. Cancel invoice in invoice module
        $this->withTenantExecutionContext($context->tenantId, function () use ($invoiceId, $context): void {
            $invoice = Invoice::query()->findOrFail($invoiceId);
            $cancelled = app(InvoiceStatusService::class)->transitionIfVersion(
                $invoice,
                InvoiceStatus::Cancelled,
                $invoice->row_version,
                $context->actorId,
                'Cancelled for test'
            );
            app(InvoiceBalanceService::class)->cancel($cancelled);
        });

        // 3. Now unlink succeeds
        $unlinkResp = $this->postJson(self::ROOT . '/expenses/allocations/' . $allocId . '/unlink-invoice')
            ->assertOk();

        $this->assertNull($unlinkResp->json('invoice_id'));
    }
}
