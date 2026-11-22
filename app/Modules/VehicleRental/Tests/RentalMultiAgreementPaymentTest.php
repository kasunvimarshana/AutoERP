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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentReceiptService;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Services\AgreementFinancialSummaryService;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalChargeDocuments;
use Modules\VehicleRental\Services\RentalPaymentService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\CurrencyFixture;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class RentalMultiAgreementPaymentTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    private const PAYMENT_PERMISSIONS = [
        PaymentPermission::PAYMENTS_VIEW,
        PaymentPermission::PAYMENTS_CREATE,
        PaymentPermission::PAYMENTS_ALLOCATE,
        PaymentPermission::PAYMENTS_REVERSE,
        PaymentPermission::PAYMENTS_REFUND,
        PaymentPermission::PAYMENTS_SUBMIT,
        PaymentPermission::PAYMENTS_APPROVE,
        PaymentPermission::PAYMENTS_POST,
        PaymentPermission::PAYMENTS_BACKDATE_CASH,
        PaymentPermission::METHODS_VIEW,
    ];

    public function test_multi_agreement_customer_payment_allocations_and_unapplied_advance(): void
    {
        $perms = array_merge(
            array_keys(RentalAuthorization::descriptions()),
            self::PAYMENT_PERMISSIONS
        );
        [$context, $customerData] = $this->loginFixture($perms, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedRentalDepositProfile($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedCustomerPaymentProfiles($context->tenantId, $context->organizationUnitId);

            $method = PaymentMethod::query()->create([
                'tenant_id' => $context->tenantId,
                'code' => 'CASH-'.Str::upper(Str::random(6)),
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $documents = app(RentalChargeDocuments::class);
            $paymentService = app(RentalPaymentService::class);
            $summaryService = app(AgreementFinancialSummaryService::class);
            $receiptService = app(PaymentReceiptService::class);

            // Create Agreement 1 for this customer
            $agr1 = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agr1 = $agreements->change(AgreementKind::Customer, $context, $agr1->id, $agr1->row_version, AgreementAction::Activate);

            // Create Agreement 2 for the SAME customer
            $customerData2 = array_merge($customerData, [
                'reference' => 'AG-CUST2-'.Str::upper(Str::random(6)),
            ]);
            $agr2 = $agreements->create(AgreementKind::Customer, $context, $customerData2);
            $agr2 = $agreements->change(AgreementKind::Customer, $context, $agr2->id, $agr2->row_version, AgreementAction::Activate);

            $customerId = (int) $agr1->customer_id;

            // Invoice 1 on Agreement 1 for 10,000
            $inv1 = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agr1,
                'debit_note',
                '10000.000000',
                'Rent Agreement 1',
                ['invoice_date' => '2026-09-01', 'exchange_rate' => '1.000000', 'expected_version' => $agr1->row_version]
            );

            // Invoice 2 on Agreement 2 for 15,000
            $inv2 = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agr2,
                'debit_note',
                '15000.000000',
                'Rent Agreement 2',
                ['invoice_date' => '2026-09-02', 'exchange_rate' => '1.000000', 'expected_version' => $agr2->row_version]
            );

            // 1. Test getPartyOpenDocuments returns both invoices and agreements
            $openDocs = $paymentService->getPartyOpenDocuments($context, AgreementKind::Customer, $customerId);
            $this->assertCount(2, $openDocs['agreements']);
            $this->assertCount(2, $openDocs['documents']);
            $this->assertSame('25000.000000', $openDocs['summary']['total_open_balance']);

            // 2. Tender a single payment of 30,000 allocating 10,000 to Inv 1, 15,000 to Inv 2, leaving 5,000 unapplied advance
            $payment = $paymentService->recordMultiAgreementPayment(
                $context,
                AgreementKind::Customer,
                $customerId,
                1,
                [
                    'payment_date' => '2026-09-05',
                    'exchange_rate' => '1.000000',
                    'reference_number' => 'MULTI-TX-001',
                    'notes' => 'Settlement across Agr 1 and Agr 2',
                ],
                [new PaymentLineData(amount: '30000.000000', paymentMethodId: $method->id)],
                [
                    ['invoice_id' => $inv1->id, 'amount' => '10000.000000'],
                    ['invoice_id' => $inv2->id, 'amount' => '15000.000000'],
                ]
            );

            // Assert payment amounts
            $this->assertSame('30000.000000', (string) $payment->total_amount);
            $this->assertSame('25000.000000', (string) $payment->allocated_amount);
            $this->assertSame('5000.000000', (string) $payment->unapplied_amount);
            $this->assertTrue($payment->metadata['multi_agreement']);
            $this->assertCount(2, $payment->metadata['agreement_references']);

            // Assert both invoices are now fully settled
            $this->assertSame('0.000000', (string) $inv1->fresh('balance')->balance->remaining_amount);
            $this->assertSame('0.000000', (string) $inv2->fresh('balance')->balance->remaining_amount);

            // Assert agreement summaries do not double-count unapplied advance
            $summary1 = $summaryService->generate(AgreementKind::Customer, $context, $agr1->id);
            $summary2 = $summaryService->generate(AgreementKind::Customer, $context, $agr2->id);

            $this->assertSame('0.000000', (string) $summary1['summary']['total_remaining_balance']);
            $this->assertSame('0.000000', (string) $summary2['summary']['total_remaining_balance']);
            $this->assertSame('5000.000000', (string) $summary1['summary']['party_unapplied_advance']);
            $this->assertSame('5000.000000', (string) $summary2['summary']['party_unapplied_advance']);

            // 3. Compile receipt and verify multi-agreement details
            $receiptData = $receiptService->getReceiptData($payment);
            $this->assertNotEmpty($receiptData['agreement_reference']);
            $this->assertStringContainsString($agr1->reference, $receiptData['agreement_reference']);
            $this->assertStringContainsString($agr2->reference, $receiptData['agreement_reference']);
            $this->assertCount(2, $receiptData['allocations']);

            // 4. Test cross-agreement unapplied advance allocation
            // Issue a third invoice on Agreement 1 for 5,000
            $inv3 = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agr1,
                'debit_note',
                '5000.000000',
                'Supplementary charge Agr 1',
                ['invoice_date' => '2026-09-15', 'exchange_rate' => '1.000000', 'expected_version' => $agr1->fresh()->row_version]
            );

            // Allocate the 5,000 unapplied advance to Inv 3
            $updatedPayment = $paymentService->allocatePartyUnappliedFunds(
                $context,
                AgreementKind::Customer,
                $customerId,
                $payment->id,
                [['invoice_id' => $inv3->id, 'amount' => '5000.000000']],
                '2026-09-15'
            );

            $this->assertSame('30000.000000', (string) $updatedPayment->total_amount);
            $this->assertSame('30000.000000', (string) $updatedPayment->allocated_amount);
            $this->assertSame('0.000000', (string) $updatedPayment->unapplied_amount);
            $this->assertSame('0.000000', (string) $inv3->fresh('balance')->balance->remaining_amount);
        });
    }

    public function test_multi_agreement_owner_disbursement(): void
    {
        $perms = array_merge(
            array_keys(RentalAuthorization::descriptions()),
            self::PAYMENT_PERMISSIONS
        );
        [$context, $customerData, $ownerData] = $this->loginFixture($perms, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedSupplierPaymentProfiles($context->tenantId, $context->organizationUnitId);

            $method = PaymentMethod::query()->create([
                'tenant_id' => $context->tenantId,
                'code' => 'BANK-'.Str::upper(Str::random(6)),
                'name' => 'Bank Transfer',
                'method_type' => PaymentMethodType::BankTransfer->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => true,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $documents = app(RentalChargeDocuments::class);
            $paymentService = app(RentalPaymentService::class);

            $agr1 = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $agr1 = $agreements->change(AgreementKind::Owner, $context, $agr1->id, $agr1->row_version, AgreementAction::Activate);

            $ownerData2 = array_merge($ownerData, [
                'reference' => 'AG-OWN2-'.Str::upper(Str::random(6)),
            ]);
            $agr2 = $agreements->create(AgreementKind::Owner, $context, $ownerData2);
            $agr2 = $agreements->change(AgreementKind::Owner, $context, $agr2->id, $agr2->row_version, AgreementAction::Activate);

            $supplierId = (int) $agr1->supplier_id;

            // Bill 1 on Agreement 1 for 40,000
            $bill1 = $documents->issueAdjustment(
                AgreementKind::Owner,
                $context,
                $agr1,
                'credit_note',
                '40000.000000',
                'Lease payable Agr 1',
                ['invoice_date' => '2026-09-01', 'exchange_rate' => '1.000000', 'expected_version' => $agr1->row_version]
            );

            // Bill 2 on Agreement 2 for 35,000
            $bill2 = $documents->issueAdjustment(
                AgreementKind::Owner,
                $context,
                $agr2,
                'credit_note',
                '35000.000000',
                'Lease payable Agr 2',
                ['invoice_date' => '2026-09-02', 'exchange_rate' => '1.000000', 'expected_version' => $agr2->row_version]
            );

            // Record multi-agreement owner disbursement of 75,000
            $payment = $paymentService->recordMultiAgreementPayment(
                $context,
                AgreementKind::Owner,
                $supplierId,
                1,
                [
                    'payment_date' => '2026-09-05',
                    'exchange_rate' => '1.000000',
                    'reference_number' => 'DISB-MULTI-01',
                ],
                [new PaymentLineData(amount: '75000.000000', paymentMethodId: $method->id, referenceNumber: 'TX-REF-01')],
                [
                    ['invoice_id' => $bill1->id, 'amount' => '40000.000000'],
                    ['invoice_id' => $bill2->id, 'amount' => '35000.000000'],
                ]
            );

            $this->assertSame('75000.000000', (string) $payment->total_amount);
            $this->assertSame('75000.000000', (string) $payment->allocated_amount);
            $this->assertSame('0.000000', (string) $payment->unapplied_amount);
            $this->assertSame('0.000000', (string) $bill1->fresh('balance')->balance->remaining_amount);
            $this->assertSame('0.000000', (string) $bill2->fresh('balance')->balance->remaining_amount);
        });
    }

    public function test_payment_reversal_restores_multi_agreement_balances(): void
    {
        $perms = array_merge(
            array_keys(RentalAuthorization::descriptions()),
            self::PAYMENT_PERMISSIONS
        );
        [$context, $customerData] = $this->loginFixture($perms, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedRentalDepositProfile($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedCustomerPaymentProfiles($context->tenantId, $context->organizationUnitId);

            $method = PaymentMethod::query()->create([
                'tenant_id' => $context->tenantId,
                'code' => 'CASH-'.Str::upper(Str::random(6)),
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $documents = app(RentalChargeDocuments::class);
            $paymentService = app(RentalPaymentService::class);

            $agr1 = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agr1 = $agreements->change(AgreementKind::Customer, $context, $agr1->id, $agr1->row_version, AgreementAction::Activate);

            $customerId = (int) $agr1->customer_id;

            $inv1 = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agr1,
                'debit_note',
                '18000.000000',
                'Rent',
                ['invoice_date' => '2026-09-01', 'exchange_rate' => '1.000000', 'expected_version' => $agr1->row_version]
            );

            $payment = $paymentService->recordMultiAgreementPayment(
                $context,
                AgreementKind::Customer,
                $customerId,
                1,
                ['payment_date' => '2026-09-05', 'exchange_rate' => '1.000000'],
                [new PaymentLineData(amount: '18000.000000', paymentMethodId: $method->id)],
                [['invoice_id' => $inv1->id, 'amount' => '18000.000000']]
            );

            $this->assertSame('0.000000', (string) $inv1->fresh('balance')->balance->remaining_amount);

            // Reverse using reversePayment
            $reversed = $paymentService->reversePayment(
                $context,
                AgreementKind::Customer,
                $agr1->id,
                $payment->id,
                'Bank reversal error',
                '2026-09-06'
            );

            $this->assertSame(PaymentDocumentStatus::Reversed, $reversed->document_status);
            $this->assertSame('18000.000000', (string) $inv1->fresh('balance')->balance->remaining_amount);
        });
    }

    public function test_multi_agreement_payment_rejects_stale_expected_version(): void
    {
        $perms = array_merge(
            array_keys(RentalAuthorization::descriptions()),
            self::PAYMENT_PERMISSIONS
        );
        [$context, $customerData] = $this->loginFixture($perms, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedCustomerPaymentProfiles($context->tenantId, $context->organizationUnitId);

            $method = PaymentMethod::query()->create([
                'tenant_id' => $context->tenantId,
                'code' => 'CASH-'.Str::upper(Str::random(6)),
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $documents = app(RentalChargeDocuments::class);
            $paymentService = app(RentalPaymentService::class);

            $agr = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agr = $agreements->change(AgreementKind::Customer, $context, $agr->id, $agr->row_version, AgreementAction::Activate);

            $inv = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agr,
                'debit_note',
                '5000.000000',
                'Rental charge',
                ['invoice_date' => '2026-09-01', 'exchange_rate' => '1.000000', 'expected_version' => $agr->row_version]
            );

            $this->expectException(ConflictHttpException::class);
            $this->expectExceptionMessage('The party or agreement record has been modified');

            $paymentService->recordMultiAgreementPayment(
                $context,
                AgreementKind::Customer,
                (int) $agr->customer_id,
                999999,
                ['payment_date' => '2026-09-05', 'exchange_rate' => '1.000000'],
                [new PaymentLineData(amount: '5000.000000', paymentMethodId: $method->id)],
                [['invoice_id' => $inv->id, 'amount' => '5000.000000']]
            );
        });
    }

    public function test_multi_agreement_payment_rejects_cross_currency_allocations(): void
    {
        $perms = array_merge(
            array_keys(RentalAuthorization::descriptions()),
            self::PAYMENT_PERMISSIONS
        );
        [$context, $customerData] = $this->loginFixture($perms, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedCustomerPaymentProfiles($context->tenantId, $context->organizationUnitId);

            $method = PaymentMethod::query()->create([
                'tenant_id' => $context->tenantId,
                'code' => 'CASH-'.Str::upper(Str::random(6)),
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $documents = app(RentalChargeDocuments::class);
            $paymentService = app(RentalPaymentService::class);

            $agr = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agr = $agreements->change(AgreementKind::Customer, $context, $agr->id, $agr->row_version, AgreementAction::Activate);

            $inv1 = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agr,
                'debit_note',
                '5000.000000',
                'Charge in base currency',
                ['invoice_date' => '2026-09-01', 'exchange_rate' => '1.000000', 'expected_version' => $agr->row_version]
            );

            $inv2 = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agr,
                'debit_note',
                '5000.000000',
                'Charge in second currency',
                ['invoice_date' => '2026-09-02', 'exchange_rate' => '1.000000', 'expected_version' => $agr->fresh()->row_version]
            );

            // Force different currency on inv2
            $usdCurrencyId = CurrencyFixture::create(['code' => 'USD']);
            $inv2->forceFill(['currency_id' => $usdCurrencyId])->save();

            $this->expectException(ValidationException::class);

            $paymentService->recordMultiAgreementPayment(
                $context,
                AgreementKind::Customer,
                (int) $agr->customer_id,
                1,
                ['payment_date' => '2026-09-05', 'exchange_rate' => '1.000000'],
                [new PaymentLineData(amount: '10000.000000', paymentMethodId: $method->id)],
                [
                    ['invoice_id' => $inv1->id, 'amount' => '5000.000000'],
                    ['invoice_id' => $inv2->id, 'amount' => '5000.000000'],
                ]
            );
        });
    }

    public function test_list_agreement_payments_queries_referenced_invoices_without_sql_error(): void
    {
        $perms = array_merge(
            array_keys(RentalAuthorization::descriptions()),
            self::PAYMENT_PERMISSIONS
        );
        [$context, $customerData] = $this->loginFixture($perms, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedRentalDepositProfile($context->tenantId, $context->organizationUnitId);
            FinancePostingFixture::seedCustomerPaymentProfiles($context->tenantId, $context->organizationUnitId);

            $method = PaymentMethod::query()->create([
                'tenant_id' => $context->tenantId,
                'code' => 'CASH-'.Str::upper(Str::random(6)),
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $documents = app(RentalChargeDocuments::class);
            $paymentService = app(RentalPaymentService::class);

            $agr = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agr = $agreements->change(AgreementKind::Customer, $context, $agr->id, $agr->row_version, AgreementAction::Activate);

            $inv = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agr,
                'debit_note',
                '12000.000000',
                'Initial lease',
                ['invoice_date' => '2026-09-01', 'exchange_rate' => '1.000000', 'expected_version' => $agr->row_version]
            );

            $payment = $paymentService->recordMultiAgreementPayment(
                $context,
                AgreementKind::Customer,
                (int) $agr->customer_id,
                1,
                ['payment_date' => '2026-09-05', 'exchange_rate' => '1.000000'],
                [new PaymentLineData(amount: '12000.000000', paymentMethodId: $method->id)],
                [['invoice_id' => $inv->id, 'amount' => '12000.000000']]
            );

            // Execute listAgreementPayments - must query invoices via whereHas('sources') without SQL column error
            $listedPayments = $paymentService->listAgreementPayments($context, $agr->id, AgreementKind::Customer);

            $this->assertNotEmpty($listedPayments);
            $this->assertSame($payment->id, $listedPayments[0]['id']);
            $this->assertSame('12000.000000', $listedPayments[0]['total_amount']);
            $this->assertCount(1, $listedPayments[0]['allocations']);
            $this->assertSame($inv->id, $listedPayments[0]['allocations'][0]['invoice_id']);
        });
    }
}
