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
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Reporting\Services\ReceivedCashSourceBreakdown;
use Tests\TestCase;

final class ReceivedCashSourceBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_cash_receipts_reconcile_across_invoice_dates_and_unapplied_amounts(): void
    {
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-CASH-'.Str::random(6),
            'name' => 'Cash source test',
            'slug' => 'cash-source-'.Str::random(6),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentId = $this->payment($tenantId, '100.000000');
        $earlierInvoiceId = $this->invoice($tenantId, '2026-06-30');
        $selectedInvoiceId = $this->invoice($tenantId, '2026-07-10');
        $laterInvoiceId = $this->invoice($tenantId, '2026-08-01');
        $reversedInvoiceId = $this->invoice($tenantId, '2026-09-01');

        $this->allocation($tenantId, $paymentId, $selectedInvoiceId, '2026-07-10', '50.000000');
        $this->allocation($tenantId, $paymentId, $earlierInvoiceId, '2026-06-30', '30.000000');
        $this->allocation($tenantId, $paymentId, $laterInvoiceId, '2026-08-01', '10.000000');
        $this->allocation($tenantId, $paymentId, $reversedInvoiceId, '2026-09-01', '5.000000', AllocationStatus::Reversed);

        $rows = collect([
            (object) ['payment_id' => $paymentId, 'payment_total' => '100.000000', 'exchange_rate' => '2.000000', 'amount' => '60.000000', 'type' => 'cash'],
            (object) ['payment_id' => $paymentId, 'payment_total' => '100.000000', 'exchange_rate' => '2.000000', 'amount' => '40.000000', 'type' => 'card'],
        ]);

        $result = app(ReceivedCashSourceBreakdown::class)->run($rows, $tenantId, null, '2026-07-01', '2026-07-31');

        self::assertSame([
            'selected_invoices' => '60.000000',
            'earlier_invoices' => '36.000000',
            'other_invoices' => '12.000000',
            'unapplied' => '12.000000',
        ], $result);
    }

    private function payment(int $tenantId, string $amount): int
    {
        return (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'payment_number' => 'PAY-CASH-SOURCE',
            'payment_type' => PaymentType::CustomerReceipt->value,
            'direction' => PaymentDirection::Inbound->value,
            'document_status' => PaymentDocumentStatus::Approved->value,
            'allocation_status' => 'partially_allocated',
            'posting_status' => PaymentPostingStatus::Posted->value,
            'payment_date' => '2026-07-20',
            'exchange_rate' => '2.000000',
            'total_amount' => $amount,
            'allocated_amount' => '90.000000',
            'unapplied_amount' => '10.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invoice(int $tenantId, string $date): int
    {
        return (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'invoice_number' => 'INV-CASH-'.$date,
            'invoice_type' => InvoiceType::Sales->value,
            'direction' => InvoiceDirection::Outbound->value,
            'invoice_date' => $date,
            'status' => InvoiceStatus::PartiallyPaid->value,
            'grand_total' => '100.000000',
            'paid_total' => '50.000000',
            'credit_total' => '0.000000',
            'balance_due' => '50.000000',
            'exchange_rate' => '1.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function allocation(int $tenantId, int $paymentId, int $invoiceId, string $invoiceDate, string $amount, AllocationStatus $status = AllocationStatus::Active): void
    {
        DB::table('payment_allocations')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => null,
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'invoice_number_snapshot' => 'INV-CASH-'.$invoiceDate,
            'invoice_date_snapshot' => $invoiceDate,
            'invoice_exchange_rate_snapshot' => '1.000000',
            'invoice_total' => '100.000000',
            'invoice_balance_before' => '100.000000',
            'allocated_amount' => $amount,
            'invoice_balance_after' => '0.000000',
            'allocation_date' => '2026-07-20',
            'status' => $status->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
