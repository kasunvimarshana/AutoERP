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
use Tests\Support\FinancePostingFixture;
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
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);

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

            // Post invoices to enable settlement
            $statuses = app(InvoiceStatusService::class);
            if ($debitNote->status === InvoiceStatus::Draft) {
                $debitNote = $statuses->transition($debitNote, InvoiceStatus::Approved, $context->actorId);
            }
            if ($debitNote->status === InvoiceStatus::Approved) {
                $debitNote = $statuses->transition($debitNote, InvoiceStatus::Posted, $context->actorId);
            }
            if ($creditNote->status === InvoiceStatus::Draft) {
                $creditNote = $statuses->transition($creditNote, InvoiceStatus::Approved, $context->actorId);
            }
            if ($creditNote->status === InvoiceStatus::Approved) {
                $creditNote = $statuses->transition($creditNote, InvoiceStatus::Posted, $context->actorId);
            }

            // Allocate the 2500 credit note against the 5000 debit note
            $balanceUpdater = app(\Modules\Invoice\Services\InvoiceBalanceUpdater::class);
            $balanceUpdater->allocateCredit($debitNote, 'credit_note', $creditNote->id, '2500.000000');

            // Verify credit note balance is now $0 and Paid
            $creditNote->refresh()->load('balance');
            $this->assertSame('0.000000', (string) $creditNote->balance->remaining_amount);
            $this->assertSame('2500.000000', (string) $creditNote->balance->credit_allocated_amount);

            // Verify financial summary now reports $0 unallocated credit and exact non-doubled credit allocated!
            $afterAllocation = $summaryService->generate(AgreementKind::Customer, $context, $customerAg->id);
            $this->assertSame('2500.000000', $afterAllocation['summary']['total_credit_notes']);
            $this->assertSame('0.000000', $afterAllocation['summary']['total_unallocated_credit']);
            $this->assertSame('2500.000000', $afterAllocation['summary']['total_credit_allocated']);
            $this->assertSame('2500.000000', $afterAllocation['summary']['total_remaining_balance']);
            $this->assertSame('2500.000000', $afterAllocation['summary']['net_account_balance']);
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

    public function test_financial_adjustment_with_target_invoice_extinguishes_target_invoice_balance(): void
    {
        [$context, $customerData] = $this->fixture();
        $this->grant($context, array_keys(\Modules\VehicleRental\Services\RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);

            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms']['base_rate'] = '30000.000000';
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            // Create and post an invoice with 10,000 balance
            $documents = app(RentalChargeDocuments::class);
            $invService = app(\Modules\Invoice\Services\InvoiceCreationService::class);
            $invFactory = app(\Modules\Invoice\Services\TaxedSourceInvoiceFactory::class);
            $statusService = app(InvoiceStatusService::class);

            $source = new \Modules\Invoice\DTOs\CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: \Modules\Invoice\Enums\InvoiceDirection::Outbound,
                invoiceDate: '2026-09-01',
                currencyId: $customerAg->currency_id,
                exchangeRate: '1.000000',
                partyType: \Modules\Invoice\Enums\InvoicePartyType::Customer->value,
                partyId: $customerAg->customer_id,
                createdBy: $context->actorId,
                notes: 'Target test invoice',
                lines: [
                    new \Modules\Invoice\DTOs\InvoiceLineData(
                        lineNumber: 1,
                        description: 'Rental Line',
                        quantity: '1.000000',
                        unitPrice: '10000.000000',
                        lineType: \Modules\Invoice\Enums\InvoiceLineType::Charge,
                        sourceLineType: 'test',
                        sourceLineId: 1,
                    ),
                ],
                sources: [
                    new \Modules\Invoice\DTOs\InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'test',
                        sourceId: 1,
                        sourceDocumentNumber: $customerAg->reference,
                        sourceDocumentDate: '2026-09-01',
                        sourceSubtotal: '10000.000000',
                        sourceGrandTotal: '10000.000000',
                    ),
                ],
                sourceLines: [
                    new \Modules\Invoice\DTOs\InvoiceSourceLineData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'test',
                        sourceId: 1,
                        sourceLineType: 'test',
                        sourceLineId: 1,
                        sourceQuantity: '1.000000',
                        invoicedQuantity: '1.000000',
                        sourceUnitPrice: '10000.000000',
                        sourceLineTotal: '10000.000000',
                        invoicedLineTotal: '10000.000000',
                    ),
                ],
            );

            $targetInv = $invService->create($invFactory->prepare(
                $source,
                \Modules\Finance\Enums\FinancePostingProfileCode::CustomerRentalInvoice,
                \Modules\Finance\Enums\FinanceAccountRoleCode::RentalRevenue,
            ));
            $targetInv = $statusService->transition($targetInv, InvoiceStatus::Approved, $context->actorId);
            $targetInv = $statusService->transition($targetInv, InvoiceStatus::Posted, $context->actorId);

            $this->assertEqualsWithDelta(10000.0, (float) $targetInv->balance->remaining_amount, 0.01);

            // Issue credit note adjustment of 4,000 targeting targetInv
            $creditNote = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $customerAg,
                'credit_note',
                '4000.000000',
                'Goodwill discount against invoice',
                [
                    'invoice_date' => '2026-09-15',
                    'exchange_rate' => '1.000000',
                    'expected_version' => $customerAg->row_version,
                    'target_invoice_id' => $targetInv->id,
                ],
            );

            // Verify target invoice balance extinguished by 4,000 (now 6,000)
            $targetInv->refresh();
            $this->assertEqualsWithDelta(6000.0, (float) $targetInv->balance->remaining_amount, 0.01);

            // Verify adjustment references the target invoice
            $adj = \Modules\VehicleRental\Models\AgreementAdjustment::query()->where('customer_agreement_id', $customerAg->id)->first();
            $this->assertSame($targetInv->id, (int) $adj->invoice_id);
        });
    }

    public function test_security_deposit_allocation_against_outstanding_invoice(): void
    {
        $perms = array_merge(
            array_keys(\Modules\VehicleRental\Services\RentalAuthorization::descriptions()),
            [
                \Modules\Payment\Constants\PaymentPermission::PAYMENTS_CREATE,
                \Modules\Payment\Constants\PaymentPermission::PAYMENTS_VIEW,
                \Modules\Payment\Constants\PaymentPermission::PAYMENTS_BACKDATE_CASH,
            ]
        );
        [$context, $customerData, $ownerData] = $this->loginFixture($perms, [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE, TenantFeature::PAYMENT]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedRentalDepositProfile($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedCustomerPaymentProfiles($context->tenantId, $context->organizationUnitId);

            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms']['base_rate'] = '30000.000000';
            $customerData['terms']['deposit_requirement'] = '20000.000000';
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            // 1. Receive deposit of 20,000
            $method = \Modules\Payment\Models\PaymentMethod::query()->create([
                'tenant_id' => $context->tenantId,
                'code' => 'CASH',
                'name' => 'Cash Payment',
                'method_type' => \Modules\Payment\Enums\PaymentMethodType::Cash,
                'direction_allowed' => 'both',
                'requires_reference' => false,
                'requires_instrument_details' => false,
                'is_active' => true,
            ]);

            $depositService = app(\Modules\VehicleRental\Services\DepositReceipts::class);
            $payment = $depositService->receive($context, $customerAg->id, (int) $customerAg->row_version, 'DEP-IDEM-01', [
                'payment_date' => '2026-09-01',
                'exchange_rate' => '1.000000',
                'notes' => 'Initial deposit',
            ], [
                new \Modules\Payment\DTOs\PaymentLineData(
                    amount: '20000.000000',
                    paymentMethodId: $method->id,
                ),
            ]);

            // 2. Create and post invoice with 15,000 balance
            $invService = app(\Modules\Invoice\Services\InvoiceCreationService::class);
            $invFactory = app(\Modules\Invoice\Services\TaxedSourceInvoiceFactory::class);
            $statusService = app(InvoiceStatusService::class);

            $source = new \Modules\Invoice\DTOs\CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: \Modules\Invoice\Enums\InvoiceDirection::Outbound,
                invoiceDate: '2026-09-01',
                currencyId: $customerAg->currency_id,
                exchangeRate: '1.000000',
                partyType: \Modules\Invoice\Enums\InvoicePartyType::Customer->value,
                partyId: $customerAg->customer_id,
                createdBy: $context->actorId,
                notes: 'Rental invoice for deposit application',
                lines: [
                    new \Modules\Invoice\DTOs\InvoiceLineData(
                        lineNumber: 1,
                        description: 'Rental Charge',
                        quantity: '1.000000',
                        unitPrice: '15000.000000',
                        lineType: \Modules\Invoice\Enums\InvoiceLineType::Charge,
                        sourceLineType: 'test',
                        sourceLineId: 2,
                    ),
                ],
                sources: [
                    new \Modules\Invoice\DTOs\InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'test',
                        sourceId: 2,
                        sourceDocumentNumber: $customerAg->reference,
                        sourceDocumentDate: '2026-09-01',
                        sourceSubtotal: '15000.000000',
                        sourceGrandTotal: '15000.000000',
                    ),
                ],
                sourceLines: [
                    new \Modules\Invoice\DTOs\InvoiceSourceLineData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'test',
                        sourceId: 2,
                        sourceLineType: 'test',
                        sourceLineId: 2,
                        sourceQuantity: '1.000000',
                        invoicedQuantity: '1.000000',
                        sourceUnitPrice: '15000.000000',
                        sourceLineTotal: '15000.000000',
                        invoicedLineTotal: '15000.000000',
                    ),
                ],
            );

            $inv = $invService->create($invFactory->prepare(
                $source,
                \Modules\Finance\Enums\FinancePostingProfileCode::CustomerRentalInvoice,
                \Modules\Finance\Enums\FinanceAccountRoleCode::RentalRevenue,
            ));
            $inv = $statusService->transition($inv, InvoiceStatus::Approved, $context->actorId);
            $inv = $statusService->transition($inv, InvoiceStatus::Posted, $context->actorId);

            // 3. Allocate 10,000 of deposit against the invoice
            $allocatedPayment = $depositService->allocateDeposit(
                $context,
                $customerAg->id,
                $payment->id,
                $inv->id,
                '10000.000000',
                '2026-09-02'
            );

            $this->assertEqualsWithDelta(10000.0, (float) $allocatedPayment->allocated_amount, 0.01);
            $inv->refresh();
            $this->assertEqualsWithDelta(5000.0, (float) $inv->balance->remaining_amount, 0.01);

            // 4. Over-allocation attempt (e.g. 15,000 when invoice balance is 5,000) must fail
            $this->expectException(\Illuminate\Validation\ValidationException::class);
            $depositService->allocateDeposit(
                $context,
                $customerAg->id,
                $payment->id,
                $inv->id,
                '15000.000000',
                '2026-09-02'
            );
        });
    }
}
