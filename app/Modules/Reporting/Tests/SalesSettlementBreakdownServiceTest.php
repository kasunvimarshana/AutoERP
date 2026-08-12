<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Reporting\Services\SalesSettlementBreakdownService;
use Tests\TestCase;

final class SalesSettlementBreakdownServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_separates_allocated_cash_card_other_methods_and_open_credit(): void
    {
        $tenantId = $this->tenant();
        $cashMethodId = $this->paymentMethod($tenantId, 'CASH', PaymentMethodType::Cash);
        $cardMethodId = $this->paymentMethod($tenantId, 'CARD', PaymentMethodType::Card);
        $bankMethodId = $this->paymentMethod($tenantId, 'BANK', PaymentMethodType::BankTransfer);

        $splitInvoiceId = $this->invoice($tenantId, 'INV-001', '1000', '750', '250');
        $bankInvoiceId = $this->invoice($tenantId, 'INV-002', '500', '500', '0');
        $this->invoice($tenantId, 'INV-003', '200', '0', '200');
        $outsidePeriodInvoiceId = $this->invoice($tenantId, 'INV-004', '900', '900', '0', '2026-06-30');

        $splitPaymentId = $this->payment($tenantId, 'PAY-001', '750');
        $this->paymentLine($tenantId, $splitPaymentId, $cashMethodId, 1, '300', PaymentMethodType::Cash);
        $this->paymentLine($tenantId, $splitPaymentId, $cardMethodId, 2, '450', PaymentMethodType::Card);
        $this->allocation($tenantId, $splitPaymentId, $splitInvoiceId, '750');

        $bankPaymentId = $this->payment($tenantId, 'PAY-002', '500');
        $this->paymentLine($tenantId, $bankPaymentId, $bankMethodId, 1, '500', PaymentMethodType::BankTransfer);
        $this->allocation($tenantId, $bankPaymentId, $bankInvoiceId, '500');

        $outsidePaymentId = $this->payment($tenantId, 'PAY-003', '900');
        $this->paymentLine($tenantId, $outsidePaymentId, $cashMethodId, 1, '900', PaymentMethodType::Cash);
        $this->allocation($tenantId, $outsidePaymentId, $outsidePeriodInvoiceId, '900');

        $result = app(SalesSettlementBreakdownService::class)->run(
            $tenantId,
            null,
            '2026-07-01',
            '2026-07-31',
        );

        self::assertSame(['amount' => '300.000000', 'document_count' => 1], $result['cash']);
        self::assertSame(['amount' => '450.000000', 'document_count' => 1], $result['card']);
        self::assertSame(['amount' => '500.000000', 'document_count' => 1], $result['other_paid']);
        self::assertSame(['amount' => '450.000000', 'document_count' => 2], $result['credit']);
        self::assertSame('0.000000', $result['credits_applied']);
    }

    private function tenant(): int
    {
        $suffix = Str::lower(Str::random(6));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-SALES-'.strtoupper($suffix),
            'name' => 'Sales Settlement '.$suffix,
            'slug' => 'sales-settlement-'.$suffix,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invoice(
        int $tenantId,
        string $number,
        string $total,
        string $paid,
        string $balance,
        string $date = '2026-07-15',
    ): int {
        return (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'invoice_number' => $number,
            'invoice_type' => InvoiceType::Sales->value,
            'direction' => InvoiceDirection::Outbound->value,
            'invoice_date' => $date,
            'status' => $balance === '0' ? InvoiceStatus::Paid->value : InvoiceStatus::PartiallyPaid->value,
            'grand_total' => $total,
            'paid_total' => $paid,
            'credit_total' => '0',
            'balance_due' => $balance,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentMethod(int $tenantId, string $code, PaymentMethodType $type): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => $code,
            'name' => Str::headline($code),
            'method_type' => $type->value,
            'direction_allowed' => 'inbound',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payment(int $tenantId, string $number, string $amount): int
    {
        return (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'payment_number' => $number,
            'payment_type' => PaymentType::CustomerReceipt->value,
            'direction' => PaymentDirection::Inbound->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'allocation_status' => 'fully_allocated',
            'posting_status' => PaymentPostingStatus::Posted->value,
            'payment_date' => '2026-07-20',
            'total_amount' => $amount,
            'allocated_amount' => $amount,
            'unapplied_amount' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function paymentLine(
        int $tenantId,
        int $paymentId,
        int $methodId,
        int $lineNumber,
        string $amount,
        PaymentMethodType $type,
    ): void {
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'payment_id' => $paymentId,
            'line_number' => $lineNumber,
            'payment_method_id' => $methodId,
            'payment_method_code_snapshot' => strtoupper($type->value),
            'payment_method_name_snapshot' => Str::headline($type->value),
            'payment_method_type_snapshot' => $type->value,
            'amount' => $amount,
            'status' => 'settled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function allocation(int $tenantId, int $paymentId, int $invoiceId, string $amount): void
    {
        DB::table('payment_allocations')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'invoice_number_snapshot' => 'Snapshot '.$invoiceId,
            'invoice_date_snapshot' => '2026-07-15',
            'invoice_total' => $amount,
            'invoice_balance_before' => $amount,
            'allocated_amount' => $amount,
            'invoice_balance_after' => '0',
            'allocation_date' => '2026-07-20',
            'status' => AllocationStatus::Active->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
