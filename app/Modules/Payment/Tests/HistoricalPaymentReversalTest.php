<?php

declare(strict_types=1);

namespace Modules\Payment\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Payment\DTOs\PaymentReversalData;
use Modules\Payment\Services\PaymentReversalService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class HistoricalPaymentReversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_whole_payment_reversal_restores_an_allocation_recorded_in_the_original_journal(): void
    {
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(), 'code' => 'HIST-PAY', 'name' => 'Historical Payment',
            'slug' => 'historical-payment', 'status' => 'active', 'status_changed_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        FinancePostingFixture::seedCustomerPaymentProfiles($tenantId);
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId, 'invoice_number' => 'HIST-INV', 'invoice_type' => 'sales',
            'direction' => 'outbound', 'invoice_date' => '2026-07-01', 'status' => 'partially_paid',
            'grand_total' => '1000.000000', 'paid_total' => '400.000000', 'balance_due' => '600.000000',
        ]);
        DB::table('invoice_balances')->insert([
            'tenant_id' => $tenantId, 'invoice_id' => $invoiceId, 'invoice_total' => '1000.000000',
            'paid_amount' => '400.000000', 'remaining_amount' => '600.000000', 'status' => 'partial',
        ]);
        $paymentId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId, 'payment_number' => 'HIST-PAY', 'payment_type' => 'customer_receipt',
            'direction' => 'inbound', 'payment_date' => '2026-07-01', 'document_status' => 'approved',
            'posting_status' => 'posted', 'allocation_status' => 'fully_allocated', 'instrument_status' => 'settled',
            'total_amount' => '400.000000', 'allocated_amount' => '400.000000',
        ]);
        $allocationId = (int) DB::table('payment_allocations')->insertGetId([
            'tenant_id' => $tenantId, 'payment_id' => $paymentId, 'invoice_id' => $invoiceId,
            'invoice_number_snapshot' => 'HIST-INV', 'invoice_exchange_rate_snapshot' => '1.000000',
            'invoice_total' => '1000.000000', 'invoice_balance_before' => '1000.000000',
            'allocated_amount' => '400.000000', 'invoice_balance_after' => '600.000000',
            'allocation_date' => '2026-07-01', 'status' => 'active', 'active_identity_slot' => 1,
        ]);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $paymentId, $invoiceId, $allocationId): void {
            // This persisted accounting shape predates independent allocation journals.
            $posted = app(FinancePostingInterface::class)->post(new PostingContext(
                source: new PostingSourceData('payment', $paymentId, $tenantId, sourceModule: 'payment'),
                postingDate: '2026-07-01',
                lines: [
                    new PostingLine(debit: '400.000000', profileKey: 'cash'),
                    new PostingLine(credit: '400.000000', profileKey: 'receivable'),
                ],
                postingProfileCode: FinancePostingProfileCode::CustomerReceipt->value,
            ));
            DB::table('payments')->where('id', $paymentId)->update(['finance_posting_reference' => $posted->journalNumber]);

            app(PaymentReversalService::class)->reverse(new PaymentReversalData(
                $paymentId, 1, '2026-07-02', 'Correct historical receipt',
            ));

            $this->assertDatabaseHas('payments', ['id' => $paymentId, 'posting_status' => 'reversed']);
            $this->assertDatabaseHas('payment_allocations', ['id' => $allocationId, 'status' => 'reversed']);
            $this->assertEquals(0, DB::table('invoice_balances')->where('invoice_id', $invoiceId)->value('paid_amount'));
            $this->assertEquals(1000, DB::table('invoice_balances')->where('invoice_id', $invoiceId)->value('remaining_amount'));
            $this->assertSame(2, DB::table('finance_journal_entries')->count());
            foreach (DB::table('finance_ledger_entries')->select('account_id')->selectRaw('SUM(base_debit) - SUM(base_credit) AS net')->groupBy('account_id')->get() as $account) {
                $this->assertEquals(0, $account->net);
            }
        });
    }
}
