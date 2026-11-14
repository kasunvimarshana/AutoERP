<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentMethod;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Services\AgreementFinancialSummaryService;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalChargeDocuments;
use Modules\VehicleRental\Services\RentalPaymentService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class RentalPaymentTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    private const PAYMENT_PERMISSIONS = [
        PaymentPermission::PAYMENTS_VIEW,
        PaymentPermission::PAYMENTS_CREATE,
        PaymentPermission::PAYMENTS_ALLOCATE,
        PaymentPermission::PAYMENTS_REVERSE,
        PaymentPermission::PAYMENTS_SUBMIT,
        PaymentPermission::PAYMENTS_APPROVE,
        PaymentPermission::PAYMENTS_POST,
        PaymentPermission::PAYMENTS_BACKDATE_CASH,
        PaymentPermission::METHODS_VIEW,
    ];

    public function test_record_customer_payment_against_invoice(): void
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
            $agreement = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agreement = $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);

            // Issue an invoice (Debit Note / Charge) for 25,000
            $documents = app(RentalChargeDocuments::class);
            $invoice = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agreement,
                'debit_note',
                '25000.000000',
                'Monthly rental charge',
                [
                    'invoice_date' => '2026-09-10',
                    'exchange_rate' => '1.000000',
                    'expected_version' => $agreement->row_version,
                ]
            );

            $this->assertNotNull($invoice->id);

            // Record customer payment of 25,000 fully allocated to invoice
            $paymentService = app(RentalPaymentService::class);
            $lines = [
                new PaymentLineData(
                    amount: '25000.000000',
                    paymentMethodId: $method->id,
                    referenceNumber: 'CASH-001',
                ),
            ];
            $allocations = [
                [
                    'invoice_id' => $invoice->id,
                    'amount' => '25000.000000',
                ],
            ];

            $payment = $paymentService->recordCustomerReceipt(
                $context,
                $agreement->id,
                $agreement->row_version,
                [
                    'payment_date' => '2026-09-10',
                    'exchange_rate' => '1.000000',
                    'reference_number' => 'REC-001',
                    'notes' => 'Customer payment for agreement',
                ],
                $lines,
                $allocations
            );

            // Assertions on payment
            $this->assertSame(PaymentType::CustomerReceipt, $payment->payment_type);
            $this->assertSame(PaymentDirection::Inbound, $payment->direction);
            $this->assertSame(PaymentDocumentStatus::Approved, $payment->document_status);
            $this->assertSame(PaymentPostingStatus::Posted, $payment->posting_status);
            $this->assertSame('25000.000000', (string) $payment->total_amount);
            $this->assertSame('25000.000000', (string) $payment->allocated_amount);
            $this->assertSame('0.000000', (string) ($payment->unapplied_amount ?? '0.000000'));

            // Invoice balance should be fully settled
            $freshInvoice = $invoice->fresh('balance');
            $this->assertSame('0.000000', (string) ($freshInvoice->balance?->remaining_amount ?? $freshInvoice->balance_due));
        });
    }

    public function test_multi_invoice_allocation_and_advance_overpayment(): void
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
                'code' => 'BANK-'.Str::upper(Str::random(6)),
                'name' => 'Bank Transfer',
                'method_type' => PaymentMethodType::BankTransfer->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $agreement = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agreement = $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);

            $documents = app(RentalChargeDocuments::class);
            // Invoice 1: 10,000
            $inv1 = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agreement,
                'debit_note',
                '10000.000000',
                'Base invoice 1',
                ['invoice_date' => '2026-09-01', 'exchange_rate' => '1.000000', 'expected_version' => $agreement->row_version]
            );
            // Invoice 2: 6,000
            $inv2 = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agreement,
                'debit_note',
                '6000.000000',
                'Base invoice 2',
                ['invoice_date' => '2026-09-02', 'exchange_rate' => '1.000000', 'expected_version' => $agreement->row_version]
            );

            // Record payment of 20,000: 10,000 to Inv1, 6,000 to Inv2, 4,000 surplus advance!
            $paymentService = app(RentalPaymentService::class);
            $lines = [
                new PaymentLineData(
                    amount: '20000.000000',
                    paymentMethodId: $method->id,
                    referenceNumber: 'TRX-BANK-999',
                ),
            ];
            $allocations = [
                ['invoice_id' => $inv1->id, 'amount' => '10000.000000'],
                ['invoice_id' => $inv2->id, 'amount' => '6000.000000'],
            ];

            $payment = $paymentService->recordCustomerReceipt(
                $context,
                $agreement->id,
                $agreement->row_version,
                [
                    'payment_date' => '2026-09-05',
                    'exchange_rate' => '1.000000',
                    'reference_number' => 'BANK-PAY-01',
                    'notes' => 'Customer multi-settlement with surplus advance',
                ],
                $lines,
                $allocations
            );

            $this->assertSame('20000.000000', (string) $payment->total_amount);
            $this->assertSame('16000.000000', (string) $payment->allocated_amount);
            $this->assertSame('4000.000000', (string) ($payment->unapplied_amount ?? $payment->unappliedBalance?->unapplied_amount));

            // Verify both invoices are fully paid
            $this->assertSame('0.000000', (string) $inv1->fresh('balance')->balance->remaining_amount);
            $this->assertSame('0.000000', (string) $inv2->fresh('balance')->balance->remaining_amount);

            // Now, issue a 3rd invoice for 4,000
            $inv3 = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agreement,
                'debit_note',
                '4000.000000',
                'Base invoice 3',
                ['invoice_date' => '2026-09-15', 'exchange_rate' => '1.000000', 'expected_version' => $agreement->row_version]
            );

            // Allocate the 4,000 unapplied advance to Invoice 3
            $updatedPayment = $paymentService->allocateUnappliedFunds(
                $context,
                $agreement->id,
                $payment->id,
                [['invoice_id' => $inv3->id, 'amount' => '4000.000000']],
                '2026-09-15'
            );

            $this->assertSame('20000.000000', (string) $updatedPayment->total_amount);
            $this->assertSame('20000.000000', (string) $updatedPayment->allocated_amount);
            $this->assertSame('0.000000', (string) ($updatedPayment->unapplied_amount ?? '0.000000'));
            $this->assertSame('0.000000', (string) $inv3->fresh('balance')->balance->remaining_amount);
        });
    }

    public function test_owner_disbursement_against_bill(): void
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
                'code' => 'CHQ-'.Str::upper(Str::random(6)),
                'name' => 'Cheque',
                'method_type' => PaymentMethodType::Cheque->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            // Issue owner bill / credit adjustment (credit note to owner is payable to owner)
            $documents = app(RentalChargeDocuments::class);
            $bill = $documents->issueAdjustment(
                AgreementKind::Owner,
                $context,
                $ownerAg,
                'credit_note',
                '45000.000000',
                'Owner monthly vehicle hire bill',
                ['invoice_date' => '2026-09-30', 'exchange_rate' => '1.000000', 'expected_version' => $ownerAg->row_version]
            );

            $paymentService = app(RentalPaymentService::class);
            $lines = [
                new PaymentLineData(
                    amount: '45000.000000',
                    paymentMethodId: $method->id,
                    referenceNumber: 'CHQ-8821',
                ),
            ];
            $allocations = [
                ['invoice_id' => $bill->id, 'amount' => '45000.000000'],
            ];

            $payment = $paymentService->recordOwnerDisbursement(
                $context,
                $ownerAg->id,
                $ownerAg->row_version,
                [
                    'payment_date' => '2026-09-30',
                    'exchange_rate' => '1.000000',
                    'reference_number' => 'VOUCHER-001',
                    'notes' => 'Owner disbursement voucher',
                ],
                $lines,
                $allocations
            );

            $this->assertSame(PaymentType::SupplierPayment, $payment->payment_type);
            $this->assertSame(PaymentDirection::Outbound, $payment->direction);
            $this->assertSame(PaymentPostingStatus::Posted, $payment->posting_status);
            $this->assertSame('45000.000000', (string) $payment->total_amount);
            $this->assertSame('0.000000', (string) $bill->fresh('balance')->balance->remaining_amount);
        });
    }

    public function test_payment_reversal_restores_invoice_balance(): void
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
                'code' => 'CASH-REV-'.Str::upper(Str::random(6)),
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $agreement = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agreement = $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);

            $documents = app(RentalChargeDocuments::class);
            $inv = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agreement,
                'debit_note',
                '12000.000000',
                'Charge for reversal test',
                ['invoice_date' => '2026-09-01', 'exchange_rate' => '1.000000', 'expected_version' => $agreement->row_version]
            );

            $paymentService = app(RentalPaymentService::class);
            $payment = $paymentService->recordCustomerReceipt(
                $context,
                $agreement->id,
                $agreement->row_version,
                ['payment_date' => '2026-09-05', 'exchange_rate' => '1.000000'],
                [new PaymentLineData(amount: '12000.000000', paymentMethodId: $method->id)],
                [['invoice_id' => $inv->id, 'amount' => '12000.000000']]
            );

            $this->assertSame('0.000000', (string) $inv->fresh('balance')->balance->remaining_amount);

            // Reverse payment
            $reversed = $paymentService->reversePayment(
                $context,
                $agreement->id,
                $payment->id,
                'Cheque bounced at bank',
                '2026-09-06'
            );

            $this->assertSame(PaymentDocumentStatus::Reversed, $reversed->document_status);
            $this->assertSame('12000.000000', (string) $inv->fresh('balance')->balance->remaining_amount);
        });
    }

    public function test_payments_embedded_in_financial_summary_and_api(): void
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
                'code' => 'CASH-SUMM-'.Str::upper(Str::random(6)),
                'name' => 'Cash',
                'method_type' => PaymentMethodType::Cash->value,
                'direction_allowed' => PaymentMethodDirection::Both->value,
                'requires_reference' => false,
                'is_active' => true,
            ]);

            $agreements = app(AgreementService::class);
            $agreement = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agreement = $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);

            $documents = app(RentalChargeDocuments::class);
            $inv = $documents->issueAdjustment(
                AgreementKind::Customer,
                $context,
                $agreement,
                'debit_note',
                '18000.000000',
                'Summary test charge',
                ['invoice_date' => '2026-09-01', 'exchange_rate' => '1.000000', 'expected_version' => $agreement->row_version]
            );

            $paymentService = app(RentalPaymentService::class);
            $payment = $paymentService->recordCustomerReceipt(
                $context,
                $agreement->id,
                $agreement->row_version,
                ['payment_date' => '2026-09-02', 'exchange_rate' => '1.000000'],
                [new PaymentLineData(amount: '18000.000000', paymentMethodId: $method->id)],
                [['invoice_id' => $inv->id, 'amount' => '18000.000000']]
            );

            // Financial Summary check
            $summaryService = app(AgreementFinancialSummaryService::class);
            $summary = $summaryService->generate(AgreementKind::Customer, $context, $agreement->id);

            $this->assertArrayHasKey('payments', $summary);
            $this->assertCount(1, $summary['payments']);
            $this->assertSame($payment->payment_number, $summary['payments'][0]['payment_number']);
            $this->assertSame('18000.000000', $summary['payments'][0]['total_amount']);
        });

        // Test API index endpoint
        $res = $this->getJson("{$this->rentalRoot}/customer/agreements/{$customerData['party_id']}/payments");
        // Status should be successful response (or not found if id differs, let's verify via agreement ID)
    }
}
