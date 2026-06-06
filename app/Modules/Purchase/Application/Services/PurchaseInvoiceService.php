<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Support\FinancialServiceSupport;
use Modules\Invoice\Application\Services\InvoiceService;

final class PurchaseInvoiceService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly InvoiceService $invoices,
    ) {}

    /** @param array<string, mixed> $options */
    public function generateFromOrder(int $purchaseOrderId, array $options = []): object
    {
        return DB::transaction(function () use ($purchaseOrderId, $options): object {
            $order = $this->lockedRow('purchase_orders', $purchaseOrderId);
            if (in_array($order->status, ['draft', 'cancelled'], true)) {
                throw ValidationException::withMessages(['status' => ['Only confirmed or received purchase orders can be invoiced.']]);
            }
            if ($this->hasGrnInvoiceLinks($purchaseOrderId)) {
                throw ValidationException::withMessages(['source' => ['This purchase order is already being invoiced from GRNs. Continue with the GRN source flow.']]);
            }

            $entries = $this->entries(
                'purchase_order',
                $purchaseOrderId,
                'purchase_order_lines',
                'purchase_order_id',
                'ordered_qty',
                $options,
            );
            $invoice = $this->createInvoice('purchase_order', $order, $entries, $options);
            $this->refreshOrderStatus($purchaseOrderId);

            return $invoice;
        });
    }

    /** @param array<string, mixed> $options */
    public function generateFromGrn(int $grnId, array $options = []): object
    {
        return DB::transaction(function () use ($grnId, $options): object {
            $grn = $this->lockedRow('grn_headers', $grnId);
            if ($grn->status !== 'posted') {
                throw ValidationException::withMessages(['status' => ['Only posted GRNs can be invoiced.']]);
            }
            if ($grn->purchase_order_id !== null && $this->hasOrderInvoiceLinks((int) $grn->purchase_order_id)) {
                throw ValidationException::withMessages(['source' => ['This purchase order is already being invoiced directly. Continue with the purchase-order source flow.']]);
            }

            $entries = $this->entries(
                'grn',
                $grnId,
                'grn_lines',
                'grn_header_id',
                'accepted_qty',
                $options,
            );
            $invoice = $this->createInvoice('grn', $grn, $entries, $options);
            $this->refreshGrnStatus($grnId);
            if ($grn->purchase_order_id !== null) {
                $this->refreshOrderStatus((int) $grn->purchase_order_id);
            }

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, array<string, mixed>>
     */
    private function entries(
        string $sourceType,
        int $sourceId,
        string $table,
        string $foreignKey,
        string $quantityColumn,
        array $options,
    ): Collection {
        $requested = [];
        foreach (array_values($options['lines'] ?? []) as $line) {
            $requested[(int) $line['source_line_id']] = (float) $line['quantity'];
        }

        $lines = DB::table($table)
            ->where('tenant_id', $this->support->tenantId())
            ->where($foreignKey, $sourceId)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->orderBy('id')
            ->get();
        $poLines = $sourceType === 'grn'
            ? DB::table('purchase_order_lines')
                ->where('tenant_id', $this->support->tenantId())
                ->whereIn('id', $lines->pluck('purchase_order_line_id')->filter()->all())
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get()
                ->keyBy('id')
            : collect();

        $entries = $lines
            ->map(function (object $line) use ($sourceType, $requested, $quantityColumn, $poLines): ?array {
                $sourceQuantity = (float) $line->{$quantityColumn};
                if ($sourceType === 'grn') {
                    $sourceQuantity = max(0, $sourceQuantity - (float) $line->returned_qty);
                }
                $remaining = round($sourceQuantity - (float) $line->invoiced_qty, 4);
                if ($sourceType === 'grn' && $line->purchase_order_line_id !== null) {
                    $poLine = $poLines->get($line->purchase_order_line_id);
                    if ($poLine !== null) {
                        $remaining = min($remaining, round(
                            (float) $poLine->ordered_qty - (float) $poLine->returned_qty - (float) $poLine->invoiced_qty,
                            4,
                        ));
                    }
                }
                if ($remaining <= 0 || ($requested !== [] && ! array_key_exists((int) $line->id, $requested))) {
                    return null;
                }
                $quantity = $requested[(int) $line->id] ?? $remaining;
                if ($quantity <= 0 || $quantity > $remaining + 0.0001) {
                    throw ValidationException::withMessages(['lines' => ['Invoice quantity cannot exceed remaining source quantity.']]);
                }
                $ratio = $sourceQuantity > 0 ? min(1, $quantity / $sourceQuantity) : 0;

                return [
                    'line' => $line,
                    'quantity' => round($quantity, 4),
                    'source_quantity' => round($sourceQuantity, 4),
                    'source_amount' => round($sourceQuantity * (float) $line->unit_price, 4),
                    'linked_amount' => round($quantity * (float) $line->unit_price, 4),
                    'line_discount' => round((float) $line->discount_amount * $ratio, 4),
                    'line_tax' => round((float) $line->tax_amount * $ratio, 4),
                ];
            })
            ->filter()
            ->values();

        if ($entries->isEmpty()) {
            throw ValidationException::withMessages(['lines' => ['The selected source has no uninvoiced quantity.']]);
        }

        return $entries;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @param  array<string, mixed>  $options
     */
    private function createInvoice(string $sourceType, object $source, Collection $entries, array $options): object
    {
        $supplier = DB::table('suppliers')
            ->where('tenant_id', $this->support->tenantId())
            ->where('id', (int) $source->supplier_id)
            ->first();
        $header = $this->headerAllocations($sourceType, $source, $entries);
        $invoice = $this->invoices->create([
            'organization_unit_id' => $source->organization_unit_id,
            'external_reference_number' => $sourceType === 'grn' ? $source->grn_number : $source->po_number,
            'document_type' => 'purchase_invoice',
            'business_context' => 'purchase',
            'ledger_direction' => 'payable',
            'balance_effect' => 'increase',
            'supplier_id' => (int) $source->supplier_id,
            'invoice_date' => $options['invoice_date'] ?? now()->toDateString(),
            'due_date' => $options['due_date'] ?? now()->addDays((int) ($supplier->payment_terms_days ?? 0))->toDateString(),
            'notes' => $options['notes'] ?? 'Generated from '.($sourceType === 'grn' ? 'GRN '.$source->grn_number : 'PO '.$source->po_number),
            'adjustments' => $this->adjustments($header['totals']),
            'lines' => $entries->map(fn (array $entry): array => [
                'item_id' => (int) $entry['line']->item_id,
                'uom_id' => (int) $entry['line']->uom_id,
                'description' => $entry['line']->description,
                'quantity' => $entry['quantity'],
                'unit_price' => (float) $entry['line']->unit_price,
                'discount_total' => $entry['line_discount'],
                'tax_total' => $entry['line_tax'],
                'charge_total' => 0,
            ])->all(),
        ]);
        $invoice = $this->invoices->issue((int) $invoice->id);
        $invoiceLines = collect($invoice->lines ?? [])->values();

        foreach ($entries as $index => $entry) {
            $sourceLine = $entry['line'];
            $invoiceLine = $invoiceLines->get($index);
            $lineHeader = $header['lines'][$index] ?? [];
            DB::table('purchase_invoice_links')->insert([
                'tenant_id' => $this->support->tenantId(),
                'organization_unit_id' => $source->organization_unit_id,
                'source_type' => $sourceType,
                'source_id' => (int) $source->id,
                'source_line_id' => (int) $sourceLine->id,
                'invoice_id' => (int) $invoice->id,
                'invoice_line_id' => $invoiceLine->id ?? null,
                'linked_quantity' => $entry['quantity'],
                'linked_amount' => $entry['linked_amount'],
                'source_quantity' => $entry['source_quantity'],
                'source_amount' => $entry['source_amount'],
                'allocated_line_discount_amount' => $entry['line_discount'],
                'allocated_header_discount_amount' => $lineHeader['header_discount_amount'] ?? 0,
                'allocated_line_tax_amount' => $entry['line_tax'],
                'allocated_header_tax_amount' => $lineHeader['header_tax_amount'] ?? 0,
                'allocated_charge_amount' => $lineHeader['charge_amount'] ?? 0,
                'allocated_debit_adjustment_amount' => $lineHeader['debit_adjustment_amount'] ?? 0,
                'allocated_credit_adjustment_amount' => $lineHeader['credit_adjustment_amount'] ?? 0,
                'allocation_ratio' => $entry['source_amount'] > 0 ? round($entry['linked_amount'] / $entry['source_amount'], 8) : 0,
                'status' => 'active',
                'linked_at' => now(),
                'created_by' => $this->support->userId(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->incrementInvoicedQuantity($sourceType, $sourceLine, (float) $entry['quantity']);
        }

        return $invoice;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array{totals: array<string, float>, lines: array<int, array<string, float>>}
     */
    private function headerAllocations(string $sourceType, object $source, Collection $entries): array
    {
        $currentGross = round((float) $entries->sum('linked_amount'), 4);
        $remainingGross = $this->remainingGross($sourceType, (int) $source->id);
        $isFinal = $currentGross + 0.0001 >= $remainingGross;
        $ratio = $remainingGross > 0 ? min(1, max(0, $currentGross / $remainingGross)) : 0;
        $remaining = [
            'header_discount_amount' => $this->remainingAmount($sourceType, (int) $source->id, (float) $source->header_discount_amount, 'allocated_header_discount_amount'),
            'header_tax_amount' => $this->remainingAmount($sourceType, (int) $source->id, (float) $source->header_tax_amount, 'allocated_header_tax_amount'),
            'charge_amount' => $this->remainingAmount($sourceType, (int) $source->id, (float) $source->header_charge_total, 'allocated_charge_amount'),
            'debit_adjustment_amount' => $this->remainingAmount($sourceType, (int) $source->id, (float) $source->header_debit_adjustment_total, 'allocated_debit_adjustment_amount'),
            'credit_adjustment_amount' => $this->remainingAmount($sourceType, (int) $source->id, (float) $source->header_credit_adjustment_total, 'allocated_credit_adjustment_amount'),
        ];
        $totals = [];
        foreach ($remaining as $key => $amount) {
            $totals[$key] = $isFinal ? round($amount, 4) : round($amount * $ratio, 4);
        }

        $weights = $entries->pluck('linked_amount')->map(fn (mixed $value): float => (float) $value)->all();
        $lines = [];
        foreach ($totals as $key => $amount) {
            foreach ($this->allocate($amount, $weights) as $index => $allocation) {
                $lines[$index][$key] = $allocation;
            }
        }

        return ['totals' => $totals, 'lines' => $lines];
    }

    /** @param array<string, float> $amounts @return array<int, array<string, mixed>> */
    private function adjustments(array $amounts): array
    {
        return collect([
            ['adjustment_type' => 'discount', 'effect' => 'deduct', 'amount' => $amounts['header_discount_amount'], 'name' => 'Header discount'],
            ['adjustment_type' => 'tax', 'effect' => 'add', 'amount' => $amounts['header_tax_amount'], 'name' => 'Header tax'],
            ['adjustment_type' => 'charge', 'effect' => 'add', 'amount' => $amounts['charge_amount'], 'name' => 'Header charge'],
            ['adjustment_type' => 'debit_adjustment', 'effect' => 'add', 'amount' => $amounts['debit_adjustment_amount'], 'name' => 'Debit adjustment'],
            ['adjustment_type' => 'credit_adjustment', 'effect' => 'deduct', 'amount' => $amounts['credit_adjustment_amount'], 'name' => 'Credit adjustment'],
        ])->filter(fn (array $adjustment): bool => (float) $adjustment['amount'] > 0)->values()->all();
    }

    private function remainingGross(string $sourceType, int $sourceId): float
    {
        $query = $sourceType === 'purchase_order'
            ? DB::table('purchase_order_lines')->where('purchase_order_id', $sourceId)
                ->selectRaw('coalesce(sum((ordered_qty - invoiced_qty) * unit_price), 0) as remaining_gross')
            : DB::table('grn_lines')->where('grn_header_id', $sourceId)
                ->selectRaw('coalesce(sum((accepted_qty - returned_qty - invoiced_qty) * unit_price), 0) as remaining_gross');

        $row = $query->where('tenant_id', $this->support->tenantId())->whereNull('deleted_at')->first();

        return round(max(0, (float) ($row->remaining_gross ?? 0)), 4);
    }

    private function remainingAmount(string $sourceType, int $sourceId, float $original, string $column): float
    {
        $allocated = (float) DB::table('purchase_invoice_links')
            ->where('tenant_id', $this->support->tenantId())
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'active')
            ->sum($column);

        return round(max(0, $original - $allocated), 4);
    }

    /** @param array<int, float> $weights @return array<int, float> */
    private function allocate(float $amount, array $weights): array
    {
        $amount = round($amount, 4);
        $total = array_sum($weights);
        if ($amount <= 0 || $total <= 0) {
            return array_fill(0, count($weights), 0.0);
        }

        $allocated = [];
        $running = 0.0;
        $last = count($weights) - 1;
        foreach (array_values($weights) as $index => $weight) {
            $share = $index === $last ? round($amount - $running, 4) : round($amount * ($weight / $total), 4);
            $allocated[$index] = $share;
            $running += $share;
        }

        return $allocated;
    }

    private function incrementInvoicedQuantity(string $sourceType, object $line, float $quantity): void
    {
        if ($sourceType === 'purchase_order') {
            DB::table('purchase_order_lines')->where('id', (int) $line->id)->increment('invoiced_qty', $quantity, ['updated_at' => now()]);

            return;
        }
        DB::table('grn_lines')->where('id', (int) $line->id)->increment('invoiced_qty', $quantity, ['updated_at' => now()]);
        if ($line->purchase_order_line_id !== null) {
            DB::table('purchase_order_lines')->where('id', (int) $line->purchase_order_line_id)->increment('invoiced_qty', $quantity, ['updated_at' => now()]);
        }
    }

    private function refreshGrnStatus(int $grnId): void
    {
        $totals = DB::table('grn_lines')
            ->selectRaw('coalesce(sum(accepted_qty - returned_qty), 0) as receivable, coalesce(sum(invoiced_qty), 0) as invoiced')
            ->where('grn_header_id', $grnId)
            ->whereNull('deleted_at')
            ->first();
        $status = (float) $totals->invoiced + 0.0001 >= (float) $totals->receivable ? 'fully_invoiced' : 'partially_invoiced';
        DB::table('grn_headers')->where('id', $grnId)->update(['invoice_status' => $status, 'updated_at' => now()]);
    }

    private function refreshOrderStatus(int $orderId): void
    {
        $totals = DB::table('purchase_order_lines')
            ->selectRaw('coalesce(sum(ordered_qty - returned_qty), 0) as receivable, coalesce(sum(invoiced_qty), 0) as invoiced')
            ->where('purchase_order_id', $orderId)
            ->whereNull('deleted_at')
            ->first();
        $status = (float) $totals->invoiced <= 0
            ? 'not_invoiced'
            : ((float) $totals->invoiced + 0.0001 >= (float) $totals->receivable ? 'fully_invoiced' : 'partially_invoiced');
        DB::table('purchase_orders')->where('id', $orderId)->update(['invoice_status' => $status, 'updated_at' => now()]);
    }

    private function lockedRow(string $table, int $id): object
    {
        $row = DB::table($table)
            ->where('tenant_id', $this->support->tenantId())
            ->where('id', $id)
            ->when(DB::getSchemaBuilder()->hasColumn($table, 'deleted_at'), fn (Builder $query): Builder => $query->whereNull('deleted_at'))
            ->lockForUpdate()
            ->first();
        if ($row === null) {
            abort(404);
        }

        return $row;
    }

    private function hasOrderInvoiceLinks(int $purchaseOrderId): bool
    {
        return DB::table('purchase_invoice_links')
            ->where('tenant_id', $this->support->tenantId())
            ->where('source_type', 'purchase_order')
            ->where('source_id', $purchaseOrderId)
            ->where('status', 'active')
            ->exists();
    }

    private function hasGrnInvoiceLinks(int $purchaseOrderId): bool
    {
        return DB::table('purchase_invoice_links')
            ->join('grn_headers', 'grn_headers.id', '=', 'purchase_invoice_links.source_id')
            ->where('purchase_invoice_links.tenant_id', $this->support->tenantId())
            ->where('purchase_invoice_links.source_type', 'grn')
            ->where('grn_headers.purchase_order_id', $purchaseOrderId)
            ->where('purchase_invoice_links.status', 'active')
            ->exists();
    }
}
