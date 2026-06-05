<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Services\FinancePostingService;
use Modules\Finance\Application\Support\FinancialServiceSupport;

final class InvoiceService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly InvoiceCalculationService $calculator,
        private readonly InvoiceStatusService $statuses,
        private readonly FinancePostingService $posting,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('invoices')
            ->select(['id', 'invoice_number', 'document_type', 'business_context', 'ledger_direction', 'customer_id', 'supplier_id', 'invoice_date', 'due_date', 'status', 'grand_total', 'settled_total', 'balance_total', 'created_at'])
            ->where('tenant_id', $this->support->tenantId())
            ->whereNull('deleted_at')
            ->when(isset($filters['status']), fn (Builder $query): Builder => $query->where('status', (string) $filters['status']))
            ->when(isset($filters['document_type']), fn (Builder $query): Builder => $query->where('document_type', (string) $filters['document_type']))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->where('invoice_number', 'like', "%$search%")->orWhere('external_reference_number', 'like', "%$search%")))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(min((int) ($filters['per_page'] ?? 20), 200), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function find(int $invoiceId): object
    {
        $invoice = DB::table('invoices')->where('tenant_id', $this->support->tenantId())->whereNull('deleted_at')->where('id', $invoiceId)->first();
        if ($invoice === null) {
            abort(404);
        }
        $invoice->lines = DB::table('invoice_lines')->where('invoice_id', $invoiceId)->orderBy('line_no')->get();
        $invoice->adjustments = DB::table('invoice_adjustments')->where('invoice_id', $invoiceId)->orderBy('sort_order')->get();
        $invoice->settlements = DB::table('invoice_settlements')->where('invoice_id', $invoiceId)->where('status', 'active')->orderBy('settlement_date')->get();

        return $invoice;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): object
    {
        return DB::transaction(function () use ($payload): object {
            $tenantId = $this->support->tenantId();
            $organizationUnitId = $this->support->organizationUnitId($payload['organization_unit_id'] ?? null);
            $lines = array_values($payload['lines'] ?? []);
            if ($lines === []) {
                throw ValidationException::withMessages(['lines' => ['At least one invoice line is required.']]);
            }
            $adjustments = array_values($payload['adjustments'] ?? []);
            $this->validateParty($payload);
            $this->validateLines($lines);
            $totals = $this->calculator->calculate($lines, $adjustments, (float) ($payload['rounding_adjustment'] ?? 0));
            $documentType = (string) ($payload['document_type'] ?? 'invoice');

            $invoiceId = DB::table('invoices')->insertGetId([
                ...$this->headerAttributes($payload, $tenantId, $organizationUnitId, $documentType),
                ...$totals,
                'created_by' => $this->support->userId(),
                'created_at' => now(),
                'updated_at' => now(),
                'row_version' => 1,
            ]);

            $this->insertLines($invoiceId, $lines);
            $this->insertAdjustments($invoiceId, $adjustments);
            $this->insertRelations($invoiceId, $payload);
            $this->statuses->record($invoiceId, null, 'draft', 'create');

            return $this->find($invoiceId);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $invoiceId, array $payload): object
    {
        return DB::transaction(function () use ($invoiceId, $payload): object {
            $tenantId = $this->support->tenantId();
            $invoice = DB::table('invoices')->where('tenant_id', $tenantId)->where('id', $invoiceId)->whereNull('deleted_at')->lockForUpdate()->first();
            if ($invoice === null) {
                abort(404);
            }
            if ($invoice->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only draft invoices can be edited.']]);
            }
            $organizationUnitId = array_key_exists('organization_unit_id', $payload) ? $this->support->organizationUnitId($payload['organization_unit_id']) : $invoice->organization_unit_id;
            $lines = array_values($payload['lines'] ?? DB::table('invoice_lines')->where('invoice_id', $invoiceId)->get()->map(fn (object $line): array => (array) $line)->all());
            $adjustments = array_values($payload['adjustments'] ?? []);
            $merged = array_merge((array) $invoice, $payload);
            $this->validateParty($merged);
            $this->validateLines($lines);
            $totals = $this->calculator->calculate($lines, $adjustments, (float) ($payload['rounding_adjustment'] ?? $invoice->rounding_adjustment));
            $documentType = (string) ($payload['document_type'] ?? $invoice->document_type);

            DB::table('invoices')->where('id', $invoiceId)->update([
                ...$this->headerAttributes($merged, $tenantId, $organizationUnitId, $documentType),
                ...$totals,
                'row_version' => ((int) $invoice->row_version) + 1,
                'updated_at' => now(),
            ]);
            DB::table('invoice_lines')->where('invoice_id', $invoiceId)->delete();
            DB::table('invoice_adjustments')->where('invoice_id', $invoiceId)->delete();
            $this->insertLines($invoiceId, $lines);
            $this->insertAdjustments($invoiceId, $adjustments);

            return $this->find($invoiceId);
        });
    }

    public function issue(int $invoiceId): object
    {
        return DB::transaction(function () use ($invoiceId): object {
            $tenantId = $this->support->tenantId();
            $invoice = DB::table('invoices')->where('tenant_id', $tenantId)->where('id', $invoiceId)->lockForUpdate()->first();
            if ($invoice === null) {
                abort(404);
            }
            if (! in_array($invoice->status, ['draft', 'issued'], true)) {
                throw ValidationException::withMessages(['status' => ['Only draft or issued invoices can be posted.']]);
            }
            if ($invoice->status === 'draft') {
                DB::table('invoices')->where('id', $invoiceId)->update([
                    'status' => 'issued',
                    'posted_by' => $this->support->userId(),
                    'posted_at' => now(),
                    'updated_at' => now(),
                    'row_version' => ((int) $invoice->row_version) + 1,
                ]);
                $this->statuses->record($invoiceId, 'draft', 'issued', 'issue');
            }
            $this->posting->postInvoice($invoiceId);
            $this->applyLinkedCreditNote($invoiceId);

            return $this->find($invoiceId);
        });
    }

    public function cancel(int $invoiceId, ?string $reason = null): object
    {
        return DB::transaction(function () use ($invoiceId, $reason): object {
            $tenantId = $this->support->tenantId();
            $invoice = DB::table('invoices')->where('tenant_id', $tenantId)->where('id', $invoiceId)->lockForUpdate()->first();
            if ($invoice === null) {
                abort(404);
            }
            if ((float) $invoice->settled_total > 0) {
                throw ValidationException::withMessages(['status' => ['Settled invoices cannot be cancelled. Create a credit note or reversal instead.']]);
            }
            DB::table('invoices')->where('id', $invoiceId)->update([
                'status' => 'cancelled',
                'balance_total' => 0,
                'cancelled_by' => $this->support->userId(),
                'cancelled_at' => now(),
                'reason' => $reason,
                'updated_at' => now(),
                'row_version' => ((int) $invoice->row_version) + 1,
            ]);
            $this->statuses->record($invoiceId, $invoice->status, 'cancelled', 'cancel', $reason);

            return $this->find($invoiceId);
        });
    }

    public function applySettlement(int $invoiceId, string $type, int $settlementId, float $amount, ?string $date = null): object
    {
        return DB::transaction(function () use ($invoiceId, $type, $settlementId, $amount, $date): object {
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => ['Settlement amount must be positive.']]);
            }
            $tenantId = $this->support->tenantId();
            $invoice = DB::table('invoices')->where('tenant_id', $tenantId)->where('id', $invoiceId)->lockForUpdate()->first();
            if ($invoice === null) {
                abort(404);
            }
            if ($amount > (float) $invoice->balance_total + 0.0001) {
                throw ValidationException::withMessages(['amount' => ['Settlement amount cannot exceed invoice balance.']]);
            }

            DB::table('invoice_settlements')->insert([
                'invoice_id' => $invoiceId,
                'settlement_type' => $type,
                'settlement_id' => $settlementId,
                'effect' => 'reduce_balance',
                'amount' => $amount,
                'base_amount' => $amount,
                'currency_id' => $invoice->currency_id,
                'exchange_rate' => $invoice->exchange_rate,
                'status' => 'active',
                'settlement_date' => $date ?? now()->toDateString(),
                'source_module' => 'payment',
                'source_type' => $type,
                'source_id' => $settlementId,
                'created_by' => $this->support->userId(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $settled = round((float) $invoice->settled_total + $amount, 4);
            $balance = round(max(0, (float) $invoice->grand_total - $settled), 4);
            $status = $this->statuses->statusForBalance((float) $invoice->grand_total, $settled, (string) $invoice->document_type);
            DB::table('invoices')->where('id', $invoiceId)->update([
                'settled_total' => $settled,
                'balance_total' => $balance,
                'status' => $status,
                'updated_at' => now(),
                'row_version' => ((int) $invoice->row_version) + 1,
            ]);
            if ($status !== $invoice->status) {
                $this->statuses->record($invoiceId, $invoice->status, $status, 'settle');
            }
            $this->updateSubledgerSettlement($invoiceId, $amount);

            return $this->find($invoiceId);
        });
    }

    public function delete(int $invoiceId): void
    {
        DB::transaction(function () use ($invoiceId): void {
            $invoice = DB::table('invoices')->where('tenant_id', $this->support->tenantId())->where('id', $invoiceId)->lockForUpdate()->first();
            if ($invoice === null) {
                abort(404);
            }
            if ($invoice->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only draft invoices can be deleted.']]);
            }
            DB::table('invoices')->where('id', $invoiceId)->update(['deleted_at' => now(), 'updated_at' => now()]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function headerAttributes(array $payload, int $tenantId, ?int $organizationUnitId, string $documentType): array
    {
        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'invoice_number' => $payload['invoice_number'] ?? $this->support->nextNumber('INV', 'invoices', 'invoice_number'),
            'external_reference_number' => $payload['external_reference_number'] ?? null,
            'document_type' => $documentType,
            'business_context' => $payload['business_context'] ?? 'manual',
            'ledger_direction' => $payload['ledger_direction'],
            'balance_effect' => $payload['balance_effect'] ?? $this->defaultBalanceEffect($documentType),
            'customer_id' => $payload['customer_id'] ?? null,
            'supplier_id' => $payload['supplier_id'] ?? null,
            'currency_id' => $payload['currency_id'] ?? null,
            'exchange_rate' => $payload['exchange_rate'] ?? 1,
            'invoice_date' => $payload['invoice_date'],
            'due_date' => $payload['due_date'] ?? null,
            'status' => $payload['status'] ?? 'draft',
            'original_invoice_id' => $payload['original_invoice_id'] ?? null,
            'reversal_of_invoice_id' => $payload['reversal_of_invoice_id'] ?? null,
            'reason_code' => $payload['reason_code'] ?? null,
            'reason' => $payload['reason'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'terms' => $payload['terms'] ?? null,
        ];
    }

    private function defaultBalanceEffect(string $documentType): string
    {
        return in_array($documentType, ['credit_adjustment', 'refund', 'reversal', 'write_off'], true) ? 'decrease' : 'increase';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateParty(array $payload): void
    {
        $direction = (string) ($payload['ledger_direction'] ?? '');
        if ($direction === 'receivable') {
            if (empty($payload['customer_id'])) {
                throw ValidationException::withMessages(['customer_id' => ['Customer is required for receivable invoices.']]);
            }
            $this->support->assertTenantRow('customers', (int) $payload['customer_id'], 'customer_id');
        } elseif ($direction === 'payable') {
            if (empty($payload['supplier_id'])) {
                throw ValidationException::withMessages(['supplier_id' => ['Supplier is required for payable invoices.']]);
            }
            $this->support->assertTenantRow('suppliers', (int) $payload['supplier_id'], 'supplier_id');
        } else {
            throw ValidationException::withMessages(['ledger_direction' => ['Ledger direction must be receivable or payable.']]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateLines(array $lines): void
    {
        foreach ($lines as $index => $line) {
            if (! empty($line['item_id'])) {
                $this->support->assertTenantRow('items', (int) $line['item_id'], "lines.$index.item_id");
            }
            if (! empty($line['uom_id'])) {
                $this->support->assertTenantRow('unit_of_measures', (int) $line['uom_id'], "lines.$index.uom_id");
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function insertLines(int $invoiceId, array $lines): void
    {
        foreach (array_values($lines) as $index => $line) {
            $quantity = (float) ($line['quantity'] ?? 1);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $gross = round($quantity * $unitPrice, 4);
            $discount = (float) ($line['discount_total'] ?? 0);
            $tax = (float) ($line['tax_total'] ?? 0);
            $charge = (float) ($line['charge_total'] ?? 0);
            DB::table('invoice_lines')->insert([
                'invoice_id' => $invoiceId,
                'line_no' => $line['line_no'] ?? $index + 1,
                'line_type' => $line['line_type'] ?? 'item',
                'item_id' => $line['item_id'] ?? null,
                'uom_id' => $line['uom_id'] ?? null,
                'item_code' => $line['item_code'] ?? null,
                'item_name' => $line['item_name'] ?? $line['description'] ?? null,
                'uom_code' => $line['uom_code'] ?? null,
                'description' => $line['description'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'gross_amount' => $gross,
                'discount_total' => $discount,
                'taxable_amount' => max(0, $gross - $discount),
                'tax_total' => $tax,
                'charge_total' => $charge,
                'net_amount' => max(0, $gross - $discount + $tax + $charge),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $adjustments
     */
    private function insertAdjustments(int $invoiceId, array $adjustments): void
    {
        foreach (array_values($adjustments) as $index => $adjustment) {
            DB::table('invoice_adjustments')->insert([
                'invoice_id' => $invoiceId,
                'level' => $adjustment['level'] ?? 'header',
                'effect' => $adjustment['effect'] === 'subtract' ? 'deduct' : $adjustment['effect'],
                'adjustment_type' => $adjustment['adjustment_type'],
                'code' => $adjustment['code'] ?? null,
                'name' => $adjustment['name'] ?? null,
                'calculation_method' => $adjustment['calculation_method'] ?? 'fixed',
                'rate' => $adjustment['rate'] ?? null,
                'base_amount' => $adjustment['base_amount'] ?? 0,
                'amount' => $adjustment['amount'],
                'is_inclusive' => $adjustment['is_inclusive'] ?? false,
                'is_compound' => $adjustment['is_compound'] ?? false,
                'sort_order' => $adjustment['sort_order'] ?? $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function insertRelations(int $invoiceId, array $payload): void
    {
        $targetId = $payload['original_invoice_id'] ?? $payload['reversal_of_invoice_id'] ?? null;
        if ($targetId === null) {
            return;
        }
        $this->support->assertTenantRow('invoices', (int) $targetId, 'original_invoice_id');
        DB::table('invoice_relations')->insertOrIgnore([
            'source_invoice_id' => $invoiceId,
            'target_invoice_id' => (int) $targetId,
            'relation_type' => match ((string) ($payload['document_type'] ?? 'invoice')) {
                'credit_adjustment' => 'credits',
                'debit_adjustment' => 'debits',
                'reversal' => 'reverses',
                default => 'adjusts',
            },
            'applied_amount' => 0,
            'status' => 'active',
            'effective_date' => $payload['invoice_date'] ?? now()->toDateString(),
            'created_by' => $this->support->userId(),
            'notes' => $payload['reason'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function applyLinkedCreditNote(int $creditInvoiceId): void
    {
        $tenantId = $this->support->tenantId();
        $credit = DB::table('invoices')->where('tenant_id', $tenantId)->where('id', $creditInvoiceId)->lockForUpdate()->first();
        if ($credit === null || $credit->document_type !== 'credit_adjustment' || $credit->original_invoice_id === null) {
            return;
        }

        $target = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->where('id', (int) $credit->original_invoice_id)
            ->lockForUpdate()
            ->first();
        if ($target === null || (float) $target->balance_total <= 0) {
            return;
        }

        $amount = round(min((float) $credit->grand_total, (float) $target->balance_total), 4);
        if ($amount <= 0 || DB::table('invoice_settlements')->where('invoice_id', (int) $target->id)->where('settlement_type', 'credit_application')->where('settlement_id', $creditInvoiceId)->where('status', 'active')->exists()) {
            return;
        }

        DB::table('invoice_settlements')->insert([
            'invoice_id' => (int) $target->id,
            'settlement_type' => 'credit_application',
            'settlement_id' => $creditInvoiceId,
            'effect' => 'reduce_balance',
            'amount' => $amount,
            'base_amount' => $amount,
            'currency_id' => $target->currency_id,
            'exchange_rate' => $target->exchange_rate,
            'status' => 'active',
            'settlement_date' => $credit->invoice_date,
            'source_module' => 'invoice',
            'source_type' => 'credit_adjustment',
            'source_id' => $creditInvoiceId,
            'created_by' => $this->support->userId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $targetSettled = round((float) $target->settled_total + $amount, 4);
        $targetBalance = round(max(0, (float) $target->grand_total - $targetSettled), 4);
        $targetStatus = $this->statuses->statusForBalance((float) $target->grand_total, $targetSettled, (string) $target->document_type);
        DB::table('invoices')->where('id', (int) $target->id)->update([
            'settled_total' => $targetSettled,
            'balance_total' => $targetBalance,
            'status' => $targetStatus,
            'updated_at' => now(),
            'row_version' => ((int) $target->row_version) + 1,
        ]);
        DB::table('invoices')->where('id', $creditInvoiceId)->update([
            'settled_total' => $amount,
            'balance_total' => round(max(0, (float) $credit->grand_total - $amount), 4),
            'status' => 'credited',
            'updated_at' => now(),
            'row_version' => ((int) $credit->row_version) + 1,
        ]);
        DB::table('invoice_relations')
            ->where('source_invoice_id', $creditInvoiceId)
            ->where('target_invoice_id', (int) $target->id)
            ->where('relation_type', 'credits')
            ->update(['applied_amount' => $amount, 'updated_at' => now()]);

        if ($targetStatus !== $target->status) {
            $this->statuses->record((int) $target->id, $target->status, $targetStatus, 'credit_application');
        }
        $this->updateSubledgerSettlement((int) $target->id, $amount);
    }

    private function updateSubledgerSettlement(int $invoiceId, float $amount): void
    {
        $link = DB::table('invoice_finance_links')
            ->where('invoice_id', $invoiceId)
            ->where('posting_role', 'primary')
            ->where('status', 'posted')
            ->first();
        if ($link === null) {
            return;
        }

        $table = $link->ar_transaction_id !== null ? 'ar_transactions' : 'ap_transactions';
        $transactionId = $link->ar_transaction_id ?? $link->ap_transaction_id;
        if ($transactionId === null) {
            return;
        }

        $transaction = DB::table($table)->where('id', (int) $transactionId)->lockForUpdate()->first();
        if ($transaction === null) {
            return;
        }

        $paid = round((float) $transaction->paid_amount + $amount, 4);
        $outstanding = round(max(0, (float) $transaction->original_amount - $paid), 4);
        DB::table($table)->where('id', (int) $transactionId)->update([
            'paid_amount' => $paid,
            'outstanding_amount' => $outstanding,
            'balance_after' => $outstanding,
            'status' => $outstanding <= 0 ? 'CLOSED' : 'OPEN',
            'updated_at' => now(),
            'row_version' => ((int) $transaction->row_version) + 1,
        ]);
    }
}
