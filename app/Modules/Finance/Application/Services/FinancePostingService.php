<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Support\FinancialServiceSupport;

final class FinancePostingService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly JournalEntryService $journals,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function postInvoice(int $invoiceId): array
    {
        return DB::transaction(function () use ($invoiceId): array {
            $tenantId = $this->support->tenantId();
            $invoice = DB::table('invoices')->where('tenant_id', $tenantId)->where('id', $invoiceId)->lockForUpdate()->first();
            if ($invoice === null) {
                throw ValidationException::withMessages(['invoice_id' => ['Invoice was not found.']]);
            }
            if (DB::table('invoice_finance_links')->where('invoice_id', $invoiceId)->where('posting_role', 'primary')->where('status', 'posted')->exists()) {
                throw ValidationException::withMessages(['invoice_id' => ['Invoice is already posted to finance.']]);
            }

            $amount = (float) $invoice->grand_total;
            $tax = (float) $invoice->tax_total;
            $body = max(0, $amount - $tax);
            $isReceivable = $invoice->ledger_direction === 'receivable';
            $isIncrease = $invoice->balance_effect === 'increase';
            $controlAccount = $isReceivable ? $this->support->accountId('accounts_receivable') : $this->support->accountId('accounts_payable');
            $bodyAccount = $isReceivable ? $this->support->accountId('sales_income') : $this->support->accountId('purchase_expense');
            $taxAccount = $this->support->accountId('tax');
            $partyType = $isReceivable ? 'customer' : 'supplier';
            $partyId = $isReceivable ? $invoice->customer_id : $invoice->supplier_id;

            $lines = [];
            if ($isReceivable === $isIncrease) {
                $lines[] = ['account_id' => $controlAccount, 'debit_amount' => $amount, 'party_type' => $partyType, 'party_id' => $partyId, 'description' => 'Invoice balance'];
                $lines[] = ['account_id' => $bodyAccount, 'credit_amount' => $body, 'description' => 'Invoice revenue/expense'];
                if ($tax > 0) {
                    $lines[] = ['account_id' => $taxAccount, 'credit_amount' => $tax, 'description' => 'Invoice tax'];
                }
            } else {
                $lines[] = ['account_id' => $controlAccount, 'credit_amount' => $amount, 'party_type' => $partyType, 'party_id' => $partyId, 'description' => 'Invoice credit/decrease'];
                $lines[] = ['account_id' => $bodyAccount, 'debit_amount' => $body, 'description' => 'Invoice credit/decrease'];
                if ($tax > 0) {
                    $lines[] = ['account_id' => $taxAccount, 'debit_amount' => $tax, 'description' => 'Tax credit/decrease'];
                }
            }

            $journal = $this->journals->createJournalEntry([
                'organization_unit_id' => $invoice->organization_unit_id,
                'reference_type' => 'invoice',
                'reference_id' => $invoiceId,
                'source_module' => 'invoice',
                'source_type' => $invoice->document_type,
                'source_id' => $invoiceId,
                'source_reference' => $invoice->invoice_number,
                'description' => 'Invoice posting '.$invoice->invoice_number,
                'entry_date' => $invoice->invoice_date,
                'currency_id' => $invoice->currency_id,
                'exchange_rate' => $invoice->exchange_rate,
                'lines' => array_values(array_filter($lines, fn (array $line): bool => ((float) ($line['debit_amount'] ?? 0)) > 0 || ((float) ($line['credit_amount'] ?? 0)) > 0)),
            ]);

            $subledgerId = $this->createSubledgerTransaction($invoice, $controlAccount);
            DB::table('invoice_finance_links')->insert([
                'invoice_id' => $invoiceId,
                'journal_entry_id' => $journal['journal_entry_id'],
                'ar_transaction_id' => $isReceivable ? $subledgerId : null,
                'ap_transaction_id' => $isReceivable ? null : $subledgerId,
                'posting_role' => 'primary',
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $this->support->userId(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['journal_entry_id' => $journal['journal_entry_id'], 'subledger_transaction_id' => $subledgerId];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function postPayment(int $paymentId): array
    {
        return DB::transaction(function () use ($paymentId): array {
            $tenantId = $this->support->tenantId();
            $payment = DB::table('payments')->where('tenant_id', $tenantId)->where('id', $paymentId)->lockForUpdate()->first();
            if ($payment === null) {
                throw ValidationException::withMessages(['payment_id' => ['Payment was not found.']]);
            }
            if ($payment->journal_entry_id !== null) {
                return ['journal_entry_id' => (int) $payment->journal_entry_id];
            }

            $cashAccountId = $payment->account_id ?: DB::table('payment_methods')->where('tenant_id', $tenantId)->where('id', $payment->payment_method_id)->value('account_id');
            if ($cashAccountId === null) {
                throw ValidationException::withMessages(['account_id' => ['Payment account is required for finance posting.']]);
            }
            $isInbound = $payment->direction === 'inbound';
            $controlAccountId = $isInbound ? $this->support->accountId('accounts_receivable') : $this->support->accountId('accounts_payable');
            $amount = (float) $payment->amount;

            $journal = $this->journals->createJournalEntry([
                'organization_unit_id' => $payment->organization_unit_id,
                'reference_type' => 'payment',
                'reference_id' => $paymentId,
                'source_module' => 'payment',
                'source_type' => $payment->direction,
                'source_id' => $paymentId,
                'source_reference' => $payment->payment_number,
                'description' => 'Payment posting '.$payment->payment_number,
                'entry_date' => $payment->payment_date,
                'currency_id' => $payment->currency_id,
                'exchange_rate' => $payment->exchange_rate,
                'lines' => $isInbound ? [
                    ['account_id' => (int) $cashAccountId, 'debit_amount' => $amount, 'description' => 'Cash/bank receipt'],
                    ['account_id' => $controlAccountId, 'credit_amount' => $amount, 'party_type' => $payment->party_type, 'party_id' => $payment->party_id, 'description' => 'Receivable settlement'],
                ] : [
                    ['account_id' => $controlAccountId, 'debit_amount' => $amount, 'party_type' => $payment->party_type, 'party_id' => $payment->party_id, 'description' => 'Payable settlement'],
                    ['account_id' => (int) $cashAccountId, 'credit_amount' => $amount, 'description' => 'Cash/bank payment'],
                ],
            ]);

            DB::table('payments')->where('id', $paymentId)->update([
                'journal_entry_id' => $journal['journal_entry_id'],
                'posted_by' => $this->support->userId(),
                'posted_at' => now(),
                'updated_at' => now(),
            ]);

            return $journal;
        });
    }

    private function createSubledgerTransaction(object $invoice, int $accountId): int
    {
        $isReceivable = $invoice->ledger_direction === 'receivable';
        $isIncrease = $invoice->balance_effect === 'increase';
        $amount = (float) $invoice->grand_total;

        return DB::table($isReceivable ? 'ar_transactions' : 'ap_transactions')->insertGetId([
            'tenant_id' => (int) $invoice->tenant_id,
            'organization_unit_id' => $invoice->organization_unit_id,
            'party_type' => $isReceivable ? 'customer' : 'supplier',
            'party_id' => $isReceivable ? $invoice->customer_id : $invoice->supplier_id,
            'account_id' => $accountId,
            'transaction_type' => $invoice->document_type,
            'reference_type' => 'invoice',
            'reference_id' => (int) $invoice->id,
            'source_module' => 'invoice',
            'source_type' => $invoice->document_type,
            'source_id' => (int) $invoice->id,
            'source_reference' => $invoice->invoice_number,
            'debit_amount' => $isReceivable === $isIncrease ? $amount : 0,
            'credit_amount' => $isReceivable === $isIncrease ? 0 : $amount,
            'original_amount' => $amount,
            'paid_amount' => (float) $invoice->settled_total,
            'outstanding_amount' => (float) $invoice->balance_total,
            'balance_after' => (float) $invoice->balance_total,
            'transaction_date' => $invoice->invoice_date,
            'due_date' => $invoice->due_date,
            'status' => ((float) $invoice->balance_total) <= 0 ? 'CLOSED' : 'OPEN',
            'currency_id' => $invoice->currency_id,
            'exchange_rate' => $invoice->exchange_rate,
            'created_by' => $this->support->userId(),
            'created_at' => now(),
            'updated_at' => now(),
            'row_version' => 1,
        ]);
    }
}
