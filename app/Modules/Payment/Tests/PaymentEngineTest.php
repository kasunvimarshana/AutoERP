<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\DTOs\PaymentRefundData;
use Modules\Payment\DTOs\PaymentReversalData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\PaymentMethod;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentRefundService;
use Modules\Payment\Services\PaymentReversalService;
use Modules\Payment\Services\PaymentSettlementService;
use Tests\TestCase;

final class PaymentEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_payment_and_tracks_line_total_as_unapplied_balance(): void
    {
        $tenantId = $this->createTenant();

        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::Advance,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-CREATE',
            lines: [
                new PaymentLineData(amount: '25000.000000'),
                new PaymentLineData(amount: '15000.000000'),
            ],
        ));

        $this->assertSame('40000.000000', (string) $payment->total_amount);
        $this->assertSame('0.000000', (string) $payment->allocated_amount);
        $this->assertSame('40000.000000', (string) $payment->unapplied_amount);
        $this->assertSame(PaymentStatus::Draft, $payment->status);
        $this->assertSame('40000.000000', (string) $payment->unappliedBalance->remaining_amount);
    }

    public function test_one_payment_can_allocate_to_many_invoices(): void
    {
        $tenantId = $this->createTenant();
        $invoiceOne = $this->createPostedInvoice($tenantId, 'INV-001', '49200.000000');
        $invoiceTwo = $this->createPostedInvoice($tenantId, 'INV-002', '73800.000000');

        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-001',
            lines: [
                new PaymentLineData(amount: '80000.000000'),
            ],
            allocations: [
                new PaymentAllocationData(
                    invoiceId: (int) $invoiceOne->getKey(),
                    allocatedAmount: '49200.000000',
                    allocationDate: '2026-06-06',
                ),
                new PaymentAllocationData(
                    invoiceId: (int) $invoiceTwo->getKey(),
                    allocatedAmount: '30800.000000',
                    allocationDate: '2026-06-06',
                ),
            ],
        ));

        $invoiceOne->refresh();
        $invoiceTwo->refresh();

        $this->assertSame('80000.000000', (string) $payment->allocated_amount);
        $this->assertSame('0.000000', (string) $payment->unapplied_amount);
        $this->assertSame(PaymentStatus::FullyAllocated, $payment->status);
        $this->assertSame('0.000000', (string) $invoiceOne->balance->remaining_amount);
        $this->assertSame('43000.000000', (string) $invoiceTwo->balance->remaining_amount);
    }

    public function test_advance_payment_can_be_partially_allocated_later_from_unapplied_balance(): void
    {
        $tenantId = $this->createTenant();
        $invoice = $this->createPostedInvoice($tenantId, 'INV-ADV', '43000.000000');

        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::Advance,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-002',
            lines: [
                new PaymentLineData(amount: '100000.000000'),
            ],
        ));

        $payment = app(PaymentAllocationService::class)->allocate($payment, [
            new PaymentAllocationData(
                invoiceId: (int) $invoice->getKey(),
                allocatedAmount: '43000.000000',
                allocationDate: '2026-06-06',
            ),
        ]);

        $this->assertSame('43000.000000', (string) $payment->allocated_amount);
        $this->assertSame('57000.000000', (string) $payment->unapplied_amount);
        $this->assertSame('57000.000000', (string) $payment->unappliedBalance->remaining_amount);
        $this->assertSame('0.000000', (string) $invoice->refresh()->balance->remaining_amount);
        $this->assertSame(PaymentStatus::PartiallyAllocated, $payment->status);
    }

    public function test_many_payments_can_allocate_to_one_invoice(): void
    {
        $tenantId = $this->createTenant();
        $invoice = $this->createPostedInvoice($tenantId, 'INV-MANY', '100000.000000');

        app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-MANY-1',
            lines: [new PaymentLineData(amount: '25000.000000')],
            allocations: [
                new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: '25000.000000',
                    allocationDate: '2026-06-06',
                ),
            ],
        ));

        app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-MANY-2',
            lines: [new PaymentLineData(amount: '30000.000000')],
            allocations: [
                new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: '30000.000000',
                    allocationDate: '2026-06-06',
                ),
            ],
        ));

        $this->assertSame('45000.000000', (string) $invoice->refresh()->balance->remaining_amount);
    }

    public function test_it_refunds_only_unapplied_balance(): void
    {
        $tenantId = $this->createTenant();
        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::Advance,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-REFUND',
            lines: [new PaymentLineData(amount: '50000.000000')],
        ));

        app(PaymentRefundService::class)->refund(new PaymentRefundData(
            paymentId: (int) $payment->getKey(),
            refundNumber: 'REF-001',
            refundDate: '2026-06-06',
            amount: '20000.000000',
        ));

        $payment->refresh();
        $this->assertSame('20000.000000', (string) $payment->refunded_amount);
        $this->assertSame('30000.000000', (string) $payment->unapplied_amount);
        $this->assertSame('30000.000000', (string) $payment->unappliedBalance->remaining_amount);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment refund cannot exceed unapplied payment balance.');

        app(PaymentRefundService::class)->refund(new PaymentRefundData(
            paymentId: (int) $payment->getKey(),
            refundNumber: 'REF-OVER',
            refundDate: '2026-06-06',
            amount: '40000.000000',
        ));
    }

    public function test_reversal_restores_invoice_balance_and_blocks_reuse(): void
    {
        $tenantId = $this->createTenant();
        $invoice = $this->createPostedInvoice($tenantId, 'INV-REV', '60000.000000');
        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-REV',
            lines: [new PaymentLineData(amount: '60000.000000')],
            allocations: [
                new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: '60000.000000',
                    allocationDate: '2026-06-06',
                ),
            ],
        ));

        $this->assertSame('0.000000', (string) $invoice->refresh()->balance->remaining_amount);

        app(PaymentReversalService::class)->reverse(new PaymentReversalData(
            paymentId: (int) $payment->getKey(),
            reversalNumber: 'REV-001',
            reversalDate: '2026-06-06',
            reason: 'Incorrect receipt',
        ));

        $this->assertSame('60000.000000', (string) $invoice->refresh()->balance->remaining_amount);
        $this->assertSame(PaymentStatus::Reversed, $payment->refresh()->status);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cancelled, void, refunded, or reversed payments cannot be allocated.');

        app(PaymentAllocationService::class)->allocate($payment, [
            new PaymentAllocationData(
                invoiceId: (int) $invoice->getKey(),
                allocatedAmount: '1000.000000',
                allocationDate: '2026-06-06',
            ),
        ]);
    }

    public function test_it_prevents_over_allocation_and_scope_mismatch(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant('OTHER');
        $invoice = $this->createPostedInvoice($tenantId, 'INV-SCOPE', '10000.000000');
        $otherTenantInvoice = $this->createPostedInvoice($otherTenantId, 'INV-OTHER', '10000.000000');

        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-SCOPE',
            lines: [new PaymentLineData(amount: '5000.000000')],
        ));

        try {
            app(PaymentAllocationService::class)->allocate($payment, [
                new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: '6000.000000',
                    allocationDate: '2026-06-06',
                ),
            ]);
            $this->fail('Expected over-allocation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Payment allocation cannot exceed available payment amount.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment invoice tenant must match payment tenant.');

        app(PaymentAllocationService::class)->allocate($payment->refresh(), [
            new PaymentAllocationData(
                invoiceId: (int) $otherTenantInvoice->getKey(),
                allocatedAmount: '1000.000000',
                allocationDate: '2026-06-06',
            ),
        ]);
    }

    public function test_it_prevents_cross_organization_allocation(): void
    {
        $tenantId = $this->createTenant();
        $orgOne = $this->createOrganizationUnit($tenantId, 'ORG-A');
        $orgTwo = $this->createOrganizationUnit($tenantId, 'ORG-B');
        $invoice = $this->createPostedInvoice($tenantId, 'INV-ORG', '10000.000000', $orgOne);

        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            organizationUnitId: $orgTwo,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-ORG',
            lines: [new PaymentLineData(amount: '5000.000000')],
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment invoice organization unit must match payment organization unit.');

        app(PaymentAllocationService::class)->allocate($payment, [
            new PaymentAllocationData(
                invoiceId: (int) $invoice->getKey(),
                allocatedAmount: '1000.000000',
                allocationDate: '2026-06-06',
            ),
        ]);
    }

    public function test_multi_method_payment_tracks_metadata_and_status_history(): void
    {
        $tenantId = $this->createTenant();
        $cash = $this->createPaymentMethod($tenantId, PaymentMethodType::Cash, 'CASH');
        $card = $this->createPaymentMethod($tenantId, PaymentMethodType::Card, 'CARD', requiresReference: true);
        $cheque = $this->createPaymentMethod($tenantId, PaymentMethodType::Cheque, 'CHQ', requiresReference: true);
        $bankTransfer = $this->createPaymentMethod($tenantId, PaymentMethodType::BankTransfer, 'BT');

        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-METHODS',
            sourceType: 'sales_order',
            sourceId: 77,
            lines: [
                new PaymentLineData(amount: '20000.000000', paymentMethodId: (int) $cash->getKey()),
                new PaymentLineData(amount: '30000.000000', paymentMethodId: (int) $card->getKey(), referenceNumber: 'AUTH-01', metadata: ['terminal' => 'T-01', 'authorization_code' => 'AUTH-01']),
                new PaymentLineData(amount: '40000.000000', paymentMethodId: (int) $cheque->getKey(), referenceNumber: 'CHQ-01', metadata: ['cheque_number' => '1001', 'value_date' => '2026-06-10']),
                new PaymentLineData(amount: '10000.000000', paymentMethodId: (int) $bankTransfer->getKey(), metadata: ['transfer_reference' => 'TR-01', 'settlement_date' => '2026-06-07']),
            ],
        ));

        $this->assertSame('100000.000000', (string) $payment->total_amount);
        $this->assertSame('sales_order', $payment->source_type);
        $this->assertSame(4, $payment->lines()->count());
        $this->assertSame('AUTH-01', $payment->lines()->where('payment_method_id', $card->getKey())->firstOrFail()->metadata['authorization_code']);
        $this->assertDatabaseHas('payment_status_histories', [
            'payment_id' => $payment->getKey(),
            'from_status' => null,
            'to_status' => 'draft',
        ]);
    }

    public function test_inactive_payment_methods_are_rejected(): void
    {
        $tenantId = $this->createTenant();
        $inactive = $this->createPaymentMethod($tenantId, PaymentMethodType::Cash, 'OFF', isActive: false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method is inactive.');
        app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-INACTIVE-METHOD',
            lines: [new PaymentLineData(amount: '10.000000', paymentMethodId: (int) $inactive->getKey())],
        ));
    }

    public function test_cross_scope_payment_methods_are_rejected(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant('METHOD-OTHER');
        $method = $this->createPaymentMethod($otherTenantId, PaymentMethodType::Cash, 'OTHER');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method tenant must match payment tenant.');
        app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-CROSS-METHOD',
            lines: [new PaymentLineData(amount: '10.000000', paymentMethodId: (int) $method->getKey())],
        ));
    }

    public function test_full_refund_marks_payment_refunded(): void
    {
        $tenantId = $this->createTenant();
        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::Advance,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-FULL-REFUND',
            lines: [new PaymentLineData(amount: '50000.000000')],
        ));

        app(PaymentRefundService::class)->refund(new PaymentRefundData(
            paymentId: (int) $payment->getKey(),
            refundNumber: 'REF-FULL',
            refundDate: '2026-06-06',
            amount: '50000.000000',
            reason: 'Customer requested refund',
        ));

        $payment->refresh();
        $this->assertSame(PaymentStatus::Refunded, $payment->status);
        $this->assertSame('0.000000', (string) $payment->unapplied_amount);

    }

    public function test_duplicate_refund_number_is_rejected(): void
    {
        $tenantId = $this->createTenant();
        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::Advance,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-DUP-REFUND',
            lines: [new PaymentLineData(amount: '50000.000000')],
        ));

        app(PaymentRefundService::class)->refund(new PaymentRefundData(
            paymentId: (int) $payment->getKey(),
            refundNumber: 'REF-DUP',
            refundDate: '2026-06-06',
            amount: '10000.000000',
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate payment refund detected.');
        app(PaymentRefundService::class)->refund(new PaymentRefundData(
            paymentId: (int) $payment->getKey(),
            refundNumber: 'REF-DUP',
            refundDate: '2026-06-06',
            amount: '10000.000000',
        ));
    }

    public function test_duplicate_reversal_is_rejected(): void
    {
        $tenantId = $this->createTenant();
        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::Advance,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-DUP-REV',
            lines: [new PaymentLineData(amount: '50000.000000')],
        ));

        app(PaymentReversalService::class)->reverse(new PaymentReversalData(
            paymentId: (int) $payment->getKey(),
            reversalNumber: 'REV-DUP-1',
            reversalDate: '2026-06-06',
            reason: 'Wrong party',
        ));

        $this->assertSame('50000.000000', (string) $payment->refresh()->reversals()->firstOrFail()->reversed_amount);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment reversal already exists for this payment.');
        app(PaymentReversalService::class)->reverse(new PaymentReversalData(
            paymentId: (int) $payment->getKey(),
            reversalNumber: 'REV-DUP-2',
            reversalDate: '2026-06-06',
            reason: 'Retry',
        ));
    }

    public function test_cheque_bank_and_card_settlement_lifecycle(): void
    {
        $tenantId = $this->createTenant();
        $cheque = $this->createPaymentMethod($tenantId, PaymentMethodType::Cheque, 'CHQ-LIFE', requiresReference: true);
        $bank = $this->createPaymentMethod($tenantId, PaymentMethodType::BankTransfer, 'BT-LIFE');
        $card = $this->createPaymentMethod($tenantId, PaymentMethodType::Card, 'CARD-LIFE');

        $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::CustomerReceipt,
            direction: PaymentDirection::Inbound,
            paymentDate: '2026-06-06',
            paymentNumber: 'PAY-SETTLE',
            lines: [
                new PaymentLineData(amount: '100.000000', paymentMethodId: (int) $cheque->getKey(), referenceNumber: 'CHQ-77'),
                new PaymentLineData(amount: '200.000000', paymentMethodId: (int) $bank->getKey(), metadata: ['transfer_reference' => 'TR-77']),
                new PaymentLineData(amount: '300.000000', paymentMethodId: (int) $card->getKey(), metadata: ['terminal' => 'POS-1']),
            ],
        ));

        $lines = $payment->lines()->orderBy('id')->get();
        $settlements = app(PaymentSettlementService::class);
        $settlements->transitionLine($payment, (int) $lines[0]->getKey(), 'received', ['remarks' => 'At counter']);
        $settlements->transitionLine($payment, (int) $lines[0]->getKey(), 'deposited', ['value_date' => '2026-06-08']);
        $chequeLine = $settlements->transitionLine($payment, (int) $lines[0]->getKey(), 'cleared');
        $bankLine = $settlements->transitionLine($payment, (int) $lines[1]->getKey(), 'settled', ['settlement_date' => '2026-06-07']);
        $cardLine = $settlements->transitionLine($payment, (int) $lines[2]->getKey(), 'authorized', ['authorization_code' => 'AUTH-77']);

        $this->assertSame('100.000000', (string) $chequeLine->cleared_amount);
        $this->assertSame('200.000000', (string) $bankLine->cleared_amount);
        $this->assertSame('authorized', (string) $cardLine->status);
        $this->assertSame('AUTH-77', $cardLine->metadata['authorization_code']);
    }

    private function createPostedInvoice(
        int $tenantId,
        string $invoiceNumber,
        string $amount,
        ?int $organizationUnitId = null,
    ): Invoice {
        $invoice = app(InvoiceCreationService::class)->create(new CreateInvoiceData(
            tenantId: $tenantId,
            invoiceType: InvoiceType::Manual,
            direction: InvoiceDirection::Outbound,
            invoiceDate: '2026-06-06',
            organizationUnitId: $organizationUnitId,
            invoiceNumber: $invoiceNumber,
            lines: [
                new InvoiceLineData(
                    lineNumber: 1,
                    description: 'Generic invoice line',
                    quantity: '1.000000',
                    unitPrice: $amount,
                ),
            ],
        ));

        $invoice = app(InvoiceStatusService::class)->transition($invoice, InvoiceStatus::Approved);

        return app(InvoiceStatusService::class)->transition($invoice, InvoiceStatus::Posted);
    }

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-PAY-'.$suffix,
            'name' => 'Payment Tenant '.$suffix,
            'slug' => 'payment-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrganizationUnit(int $tenantId, string $code): int
    {
        return (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$code,
            'code' => $code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPaymentMethod(
        int $tenantId,
        PaymentMethodType $type,
        string $code,
        bool $requiresReference = false,
        bool $isActive = true,
    ): PaymentMethod {
        return PaymentMethod::query()->create([
            'tenant_id' => $tenantId,
            'code' => $code.'-'.Str::upper(Str::random(5)),
            'name' => Str::headline($code),
            'method_type' => $type->value,
            'direction_allowed' => 'both',
            'requires_reference' => $requiresReference,
            'requires_bank_account' => false,
            'is_active' => $isActive,
            'sort_order' => 1,
        ]);
    }
}
