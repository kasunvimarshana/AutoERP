<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Sequence\Domain\Services\SequenceService;

final class JournalEntryService
{
    public function __construct(private readonly SequenceService $sequences)
    {
    }

    /** @param array<string, mixed> $grn */
    public function createPurchaseAccrual(array $grn): int
    {
        $existing = $this->existingPostedEntry('PURCHASE_GRN', (int) $grn['id'], (int) $grn['tenant_id']);
        if ($existing !== null) {
            return $existing;
        }

        $lines = $this->sourceLines($grn, 'grn_lines', 'grn_header_id');
        $netAmount = max(0.0, round((float) $grn['grand_total'] - (float) ($grn['tax_total'] ?? 0), 4));
        if ($netAmount <= 0) {
            throw new \RuntimeException('GRN accrual amount must be greater than zero.');
        }

        return $this->createBalancedEntry($grn, 'PURCHASE_GRN', (int) $grn['id'], 'Purchase goods receipt accrual', [
            $this->postingLine($this->purchaseGoodsAccount($lines->first(), (int) $grn['tenant_id']), $netAmount, 0.0, 'Inventory or expense received'),
            $this->postingLine($this->fallbackAccount((int) $grn['tenant_id'], 'LIABILITY'), 0.0, $netAmount, 'Goods received not invoiced'),
        ]);
    }

    /** @param array<string, mixed> $invoice */
    public function createPurchaseInvoice(array $invoice): int
    {
        $existing = $this->existingPostedEntry('PURCHASE_INVOICE', (int) $invoice['id'], (int) $invoice['tenant_id']);
        if ($existing !== null) {
            return $existing;
        }

        $tenantId = (int) $invoice['tenant_id'];
        $lines = $this->sourceLines($invoice, 'invoice_lines', 'invoice_id');
        $grandTotal = (float) $invoice['grand_total'];
        $taxTotal = (float) ($invoice['tax_total'] ?? 0);
        $netAmount = max(0.0, round($grandTotal - $taxTotal, 4));
        $postingLines = [];

        foreach ($this->allocateNetAmountByAccount($lines, $netAmount, $tenantId) as $accountId => $amount) {
            if ($amount > 0) {
                $postingLines[] = $this->postingLine((int) $accountId, $amount, 0.0, 'Purchase invoice line amount');
            }
        }

        foreach ($this->taxPostingLines($lines, $tenantId, true) as $line) {
            $postingLines[] = $line;
        }

        $postingLines[] = $this->postingLine(
            $this->apAccount($invoice),
            0.0,
            $grandTotal,
            'Supplier payable',
        );

        return $this->createBalancedEntry($invoice, 'PURCHASE_INVOICE', (int) $invoice['id'], 'Purchase invoice', $postingLines);
    }

    /** @param array<string, mixed> $invoice */
    public function createSalesInvoice(array $invoice): int
    {
        return $this->simpleEntry($invoice, 'SALES_INVOICE', (int) $invoice['id'], 'ASSET', 'INCOME', (float) $invoice['grand_total']);
    }

    /** @param array<string, mixed> $invoice */
    public function createServiceInvoice(array $invoice): int
    {
        return $this->simpleEntry($invoice, 'SERVICE_INVOICE', (int) $invoice['id'], 'ASSET', 'INCOME', (float) $invoice['grand_total']);
    }

    /** @param array<string, mixed> $payment */
    public function createPaymentEntry(array $payment): int
    {
        $referenceType = (string) (($payment['direction'] ?? 'inbound') === 'outbound' ? 'PAYMENT_OUT' : 'PAYMENT_IN');
        $existing = $this->existingPostedEntry($referenceType, (int) $payment['id'], (int) $payment['tenant_id']);
        if ($existing !== null) {
            return $existing;
        }

        $amount = (float) $payment['amount'];
        if ($amount <= 0) {
            throw new \RuntimeException('Payment journal amount must be greater than zero.');
        }

        if (($payment['direction'] ?? 'inbound') === 'outbound') {
            $lines = [
                $this->postingLine($this->apAccount($payment), $amount, 0.0, 'Reduce supplier payable'),
                $this->postingLine((int) $payment['account_id'], 0.0, $amount, 'Cash or bank disbursement'),
            ];
        } else {
            $lines = [
                $this->postingLine((int) $payment['account_id'], $amount, 0.0, 'Cash or bank receipt'),
                $this->postingLine($this->fallbackAccount((int) $payment['tenant_id'], 'ASSET'), 0.0, $amount, 'Customer receivable settlement'),
            ];
        }

        return $this->createBalancedEntry($payment, $referenceType, (int) $payment['id'], 'Payment posting', $lines);
    }

    /** @param array<string, mixed> $advance */
    public function createAdvancePaymentEntry(array $advance): int
    {
        $existing = $this->existingPostedEntry('SUPPLIER_ADVANCE', (int) $advance['id'], (int) $advance['tenant_id']);
        if ($existing !== null) {
            return $existing;
        }

        $payment = empty($advance['payment_id']) ? null : DB::table('payments')->find((int) $advance['payment_id']);
        $cashAccountId = $payment?->account_id === null
            ? $this->fallbackAccount((int) $advance['tenant_id'], 'ASSET')
            : (int) $payment->account_id;

        return $this->createBalancedEntry($advance, 'SUPPLIER_ADVANCE', (int) $advance['id'], 'Supplier advance payment', [
            $this->postingLine($this->fallbackAccount((int) $advance['tenant_id'], 'ASSET'), (float) $advance['amount'], 0.0, 'Supplier prepayment asset'),
            $this->postingLine($cashAccountId, 0.0, (float) $advance['amount'], 'Cash or bank disbursement'),
        ]);
    }

    /** @param array<string, mixed> $return */
    public function createPurchaseReturnEntry(array $return): int
    {
        $existing = $this->existingPostedEntry('PURCHASE_RETURN', (int) $return['id'], (int) $return['tenant_id']);
        if ($existing !== null) {
            return $existing;
        }

        $tenantId = (int) $return['tenant_id'];
        $lines = $this->sourceLines($return, 'purchase_return_lines', 'purchase_return_id');
        $grandTotal = (float) $return['grand_total'];
        $taxTotal = (float) ($return['tax_total'] ?? 0);
        $netAmount = max(0.0, round($grandTotal - $taxTotal, 4));
        $postingLines = [
            $this->postingLine($this->apAccount($return), $grandTotal, 0.0, 'Reduce supplier payable'),
        ];

        foreach ($this->allocateNetAmountByAccount($lines, $netAmount, $tenantId, true) as $accountId => $amount) {
            if ($amount > 0) {
                $postingLines[] = $this->postingLine((int) $accountId, 0.0, $amount, 'Inventory or purchase return reduction');
            }
        }

        foreach ($this->taxPostingLines($lines, $tenantId, false) as $line) {
            $postingLines[] = $line;
        }

        return $this->createBalancedEntry($return, 'PURCHASE_RETURN', (int) $return['id'], 'Purchase return', $postingLines);
    }

    /** @param array<string, mixed> $return */
    public function createSalesReturnEntry(array $return): int
    {
        return $this->simpleEntry($return, 'SALES_RETURN', (int) $return['id'], 'INCOME', 'ASSET', (float) $return['grand_total']);
    }

    /** @param array<string, mixed> $source */
    private function simpleEntry(array $source, string $referenceType, int $referenceId, string $debitType, string $creditType, float $amount): int
    {
        $existing = $this->existingPostedEntry($referenceType, $referenceId, (int) $source['tenant_id']);
        if ($existing !== null) {
            return $existing;
        }

        return $this->createBalancedEntry($source, $referenceType, $referenceId, $referenceType, [
            $this->postingLine($this->fallbackAccount((int) $source['tenant_id'], $debitType), $amount, 0.0, $referenceType),
            $this->postingLine($this->fallbackAccount((int) $source['tenant_id'], $creditType), 0.0, $amount, $referenceType),
        ]);
    }

    /**
     * @param array<string, mixed> $source
     * @param array<int, array<string, mixed>> $lines
     */
    private function createBalancedEntry(array $source, string $referenceType, int $referenceId, string $description, array $lines): int
    {
        $tenantId = (int) $source['tenant_id'];
        $organizationUnitId = isset($source['organization_unit_id']) ? (int) $source['organization_unit_id'] : null;
        $debit = round(array_sum(array_column($lines, 'debit_amount')), 4);
        $credit = round(array_sum(array_column($lines, 'credit_amount')), 4);

        if ($debit <= 0 || abs($debit - $credit) > 0.0001) {
            throw new \RuntimeException('Journal entry is not balanced.');
        }

        $entryId = (int) DB::table('journal_entries')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'entry_number' => $this->sequences->next('journal', $tenantId, $organizationUnitId),
            'entry_type' => 'AUTO',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'entry_date' => now()->toDateString(),
            'posting_date' => now()->toDateString(),
            'status' => 'POSTED',
            'posted_at' => now(),
            'created_by' => $source['created_by'] ?? null,
            'posted_by' => $source['created_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (array_values($lines) as $index => $line) {
            DB::table('journal_entry_lines')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'journal_entry_id' => $entryId,
                'account_id' => (int) $line['account_id'],
                'description' => $line['description'] ?? null,
                'debit_amount' => (float) $line['debit_amount'],
                'credit_amount' => (float) $line['credit_amount'],
                'currency_id' => $source['currency_id'] ?? null,
                'exchange_rate' => $source['exchange_rate'] ?? 1,
                'base_debit_amount' => round((float) $line['debit_amount'] * (float) ($source['exchange_rate'] ?? 1), 4),
                'base_credit_amount' => round((float) $line['credit_amount'] * (float) ($source['exchange_rate'] ?? 1), 4),
                'tax_rate_id' => $line['tax_rate_id'] ?? null,
                'tax_amount' => $line['tax_amount'] ?? 0,
                'line_number' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $entryId;
    }

    /** @return array<string, mixed> */
    private function postingLine(int $accountId, float $debit, float $credit, ?string $description = null, ?int $taxRateId = null, float $taxAmount = 0): array
    {
        return [
            'account_id' => $accountId,
            'debit_amount' => round($debit, 4),
            'credit_amount' => round($credit, 4),
            'description' => $description,
            'tax_rate_id' => $taxRateId,
            'tax_amount' => round($taxAmount, 4),
        ];
    }

    /** @param array<string, mixed> $source */
    private function apAccount(array $source): int
    {
        if (! empty($source['ap_account_id'])) {
            return (int) $source['ap_account_id'];
        }

        if (($source['party_type'] ?? 'supplier') === 'supplier' && ! empty($source['party_id'])) {
            $accountId = DB::table('suppliers')->where('id', (int) $source['party_id'])->value('ap_account_id');
            if ($accountId !== null) {
                return (int) $accountId;
            }
        }

        if (! empty($source['supplier_id'])) {
            $accountId = DB::table('suppliers')->where('id', (int) $source['supplier_id'])->value('ap_account_id');
            if ($accountId !== null) {
                return (int) $accountId;
            }
        }

        return $this->fallbackAccount((int) $source['tenant_id'], 'LIABILITY');
    }

    /** @param array<string, mixed>|null $line */
    private function purchaseGoodsAccount(?array $line, int $tenantId, bool $preferReturnAccount = false): int
    {
        if (! empty($line['account_id'])) {
            return (int) $line['account_id'];
        }

        if (! empty($line['item_id'])) {
            $item = DB::table('items')->find((int) $line['item_id']);
            if ($item !== null) {
                $preferred = $preferReturnAccount
                    ? ($item->purchase_return_account_id ?? null)
                    : (($item->is_stockable ?? false) ? ($item->inventory_account_id ?? null) : ($item->expense_account_id ?? null));
                $fallback = $item->inventory_account_id ?? $item->expense_account_id ?? null;

                if ($preferred !== null || $fallback !== null) {
                    return (int) ($preferred ?? $fallback);
                }
            }
        }

        return $this->fallbackAccount($tenantId, 'ASSET');
    }

    /**
     * @param Collection<int, array<string, mixed>> $lines
     * @return array<int, float>
     */
    private function allocateNetAmountByAccount(Collection $lines, float $netAmount, int $tenantId, bool $preferReturnAccount = false): array
    {
        if ($netAmount <= 0) {
            return [];
        }

        $lineNetTotal = (float) $lines->sum(static fn (array $line): float => (float) ($line['line_total'] ?? 0));
        $allocated = [];

        if ($lines->isEmpty() || $lineNetTotal <= 0) {
            $allocated[$this->fallbackAccount($tenantId, 'ASSET')] = $netAmount;

            return $allocated;
        }

        foreach ($lines as $line) {
            $accountId = $this->purchaseGoodsAccount($line, $tenantId, $preferReturnAccount);
            $amount = round($netAmount * ((float) ($line['line_total'] ?? 0) / $lineNetTotal), 4);
            $allocated[$accountId] = round(($allocated[$accountId] ?? 0) + $amount, 4);
        }

        $difference = round($netAmount - array_sum($allocated), 4);
        if (abs($difference) > 0 && $allocated !== []) {
            $firstAccount = array_key_first($allocated);
            $allocated[$firstAccount] = round($allocated[$firstAccount] + $difference, 4);
        }

        return $allocated;
    }

    /**
     * @param Collection<int, array<string, mixed>> $lines
     * @return array<int, array<string, mixed>>
     */
    private function taxPostingLines(Collection $lines, int $tenantId, bool $debit): array
    {
        $postingLines = [];
        $taxGroups = [];

        foreach ($lines as $line) {
            if (empty($line['tax_group_id']) || (float) ($line['tax_amount'] ?? 0) <= 0) {
                continue;
            }

            $taxGroups[(int) $line['tax_group_id']] = round(($taxGroups[(int) $line['tax_group_id']] ?? 0) + (float) $line['tax_amount'], 4);
        }

        foreach ($taxGroups as $taxGroupId => $amount) {
            $taxRate = DB::table('tax_rates')
                ->where('tax_group_id', $taxGroupId)
                ->where('is_active', true)
                ->whereNotNull('account_id')
                ->orderBy('id')
                ->first();
            $accountId = $taxRate?->account_id === null
                ? $this->fallbackAccount($tenantId, 'ASSET')
                : (int) $taxRate->account_id;

            $postingLines[] = $this->postingLine(
                $accountId,
                $debit ? $amount : 0.0,
                $debit ? 0.0 : $amount,
                'Purchase tax',
                $taxRate?->id === null ? null : (int) $taxRate->id,
                $amount,
            );
        }

        return $postingLines;
    }

    /** @param array<string, mixed> $source */
    private function sourceLines(array $source, string $table, string $foreignKey): Collection
    {
        if (! empty($source['lines']) && is_iterable($source['lines'])) {
            return collect($source['lines'])->map(static fn ($line): array => (array) $line)->values();
        }

        return DB::table($table)
            ->where($foreignKey, (int) $source['id'])
            ->get()
            ->map(static fn (object $line): array => (array) $line)
            ->values();
    }

    private function existingPostedEntry(string $referenceType, int $referenceId, int $tenantId): ?int
    {
        $entryId = DB::table('journal_entries')
            ->where('tenant_id', $tenantId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', 'POSTED')
            ->value('id');

        return $entryId === null ? null : (int) $entryId;
    }

    private function fallbackAccount(int $tenantId, string $type): int
    {
        $accountId = DB::table('accounts')
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('is_active', true)
            ->where('allows_manual_posting', true)
            ->orderBy('id')
            ->value('id');

        if ($accountId === null) {
            throw new \RuntimeException("Unable to resolve {$type} posting account.");
        }

        return (int) $accountId;
    }
}
