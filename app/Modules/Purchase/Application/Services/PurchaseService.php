<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Application\Support\FinancialServiceSupport;
use Modules\Inventory\Application\Services\StockIssuingService;
use Modules\Inventory\Application\Services\StockReceivingService;
use Modules\Invoice\Application\Services\InvoiceService;

final class PurchaseService
{
    public function __construct(
        private readonly FinancialServiceSupport $support,
        private readonly StockReceivingService $stockReceiving,
        private readonly StockIssuingService $stockIssuing,
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateOrders(array $filters): LengthAwarePaginator
    {
        $tenantId = $this->support->tenantId();
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('purchase_orders')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'purchase_orders.warehouse_id')
            ->select([
                'purchase_orders.id',
                'purchase_orders.po_number',
                'purchase_orders.supplier_id',
                'suppliers.supplier_name',
                'purchase_orders.warehouse_id',
                'warehouses.name as warehouse_name',
                'purchase_orders.order_date',
                'purchase_orders.expected_date',
                'purchase_orders.status',
                'purchase_orders.invoice_status',
                'purchase_orders.subtotal',
                'purchase_orders.line_tax_total',
                'purchase_orders.line_discount_total',
                'purchase_orders.header_discount_type',
                'purchase_orders.header_discount_value',
                'purchase_orders.header_discount_amount',
                'purchase_orders.header_tax_group_id',
                'purchase_orders.header_tax_amount',
                'purchase_orders.discount_total',
                'purchase_orders.tax_total',
                'purchase_orders.debit_note_total',
                'purchase_orders.credit_note_total',
                'purchase_orders.grand_total',
                'purchase_orders.paid_amount',
                'purchase_orders.balance',
                'purchase_orders.created_at',
            ])
            ->where('purchase_orders.tenant_id', $tenantId)
            ->whereNull('purchase_orders.deleted_at')
            ->when(isset($filters['status']), fn (Builder $query): Builder => $query->where('purchase_orders.status', (string) $filters['status']))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q
                ->where('purchase_orders.po_number', 'like', "%$search%")
                ->orWhere('purchase_orders.reference', 'like', "%$search%")
                ->orWhere('suppliers.supplier_name', 'like', "%$search%")))
            ->orderByDesc('purchase_orders.order_date')
            ->orderByDesc('purchase_orders.id')
            ->paginate(min((int) ($filters['per_page'] ?? 20), 200), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function findOrder(int $id): object
    {
        $order = DB::table('purchase_orders')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'purchase_orders.warehouse_id')
            ->select(['purchase_orders.*', 'suppliers.supplier_name', 'warehouses.name as warehouse_name'])
            ->where('purchase_orders.tenant_id', $this->support->tenantId())
            ->whereNull('purchase_orders.deleted_at')
            ->where('purchase_orders.id', $id)
            ->first();

        if ($order === null) {
            abort(404);
        }

        $order->lines = $this->orderLines($id);
        $order->grns = DB::table('grn_headers')
            ->select(['id', 'grn_number', 'status', 'invoice_status', 'received_date', 'grand_total'])
            ->where('tenant_id', $this->support->tenantId())
            ->where('purchase_order_id', $id)
            ->whereNull('deleted_at')
            ->orderByDesc('received_date')
            ->get();
        $order->supplier_balance = $this->supplierOutstanding((int) $order->supplier_id);

        return $order;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createOrder(array $payload): object
    {
        return DB::transaction(function () use ($payload): object {
            $tenantId = $this->support->tenantId();
            $organizationUnitId = $this->support->organizationUnitId($payload['organization_unit_id'] ?? null);
            $lines = array_values($payload['lines'] ?? []);
            $this->validateOrderHeader($payload);
            $this->validateLines($lines);
            $totals = $this->totals($lines, 'ordered_qty', false, $payload);

            $orderId = DB::table('purchase_orders')->insertGetId([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'supplier_id' => (int) $payload['supplier_id'],
                'warehouse_id' => (int) $payload['warehouse_id'],
                'po_number' => $payload['po_number'] ?? $this->support->nextNumber('PO', 'purchase_orders', 'po_number'),
                'reference' => $payload['reference'] ?? null,
                'status' => 'draft',
                'invoice_status' => 'not_invoiced',
                'currency_id' => $payload['currency_id'] ?? null,
                'exchange_rate' => $payload['exchange_rate'] ?? 1,
                'order_date' => $payload['order_date'],
                'expected_date' => $payload['expected_date'] ?? null,
                ...$totals,
                'paid_amount' => 0,
                'balance' => $totals['grand_total'],
                'notes' => $payload['notes'] ?? null,
                'created_by' => $this->support->userId(),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->insertOrderLines($orderId, $tenantId, $organizationUnitId, $lines);
            $this->recordStatus('purchase_order', $orderId, null, 'draft');

            return $this->findOrder($orderId);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateOrder(int $id, array $payload): object
    {
        return DB::transaction(function () use ($id, $payload): object {
            $tenantId = $this->support->tenantId();
            $order = $this->lockedRow('purchase_orders', $id);
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only draft purchase orders can be edited.']]);
            }

            $merged = array_merge((array) $order, $payload);
            $lines = array_values($payload['lines'] ?? $this->orderLines($id)->map(fn (object $line): array => (array) $line)->all());
            $organizationUnitId = array_key_exists('organization_unit_id', $payload)
                ? $this->support->organizationUnitId($payload['organization_unit_id'])
                : $order->organization_unit_id;
            $this->validateOrderHeader($merged);
            $this->validateLines($lines);
            $totals = $this->totals($lines, 'ordered_qty', false, $merged);

            DB::table('purchase_orders')->where('id', $id)->update([
                'organization_unit_id' => $organizationUnitId,
                'supplier_id' => (int) $merged['supplier_id'],
                'warehouse_id' => (int) $merged['warehouse_id'],
                'po_number' => $merged['po_number'],
                'reference' => $merged['reference'] ?? null,
                'order_date' => $merged['order_date'],
                'expected_date' => $merged['expected_date'] ?? null,
                ...$totals,
                'balance' => $totals['grand_total'],
                'notes' => $merged['notes'] ?? null,
                'updated_by' => $this->support->userId(),
                'row_version' => ((int) $order->row_version) + 1,
                'updated_at' => now(),
            ]);
            DB::table('purchase_order_lines')->where('purchase_order_id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
            $this->insertOrderLines($id, $tenantId, $organizationUnitId, $lines);

            return $this->findOrder($id);
        });
    }

    public function confirmOrder(int $id): object
    {
        return $this->transitionOrder($id, ['draft'], 'confirmed', ['confirmed_by' => $this->support->userId(), 'confirmed_at' => now()]);
    }

    public function closeOrder(int $id, ?string $reason = null): object
    {
        return DB::transaction(function () use ($id, $reason): object {
            $order = $this->lockedRow('purchase_orders', $id);
            if (! in_array($order->status, ['confirmed', 'partially_received', 'received'], true)) {
                throw ValidationException::withMessages(['status' => ['Only confirmed or received purchase orders can be closed.']]);
            }

            $this->updateStatus('purchase_orders', $id, $order->status, 'closed', [], $reason);

            return $this->findOrder($id);
        });
    }

    public function cancelOrder(int $id, ?string $reason = null): object
    {
        return DB::transaction(function () use ($id, $reason): object {
            $order = $this->lockedRow('purchase_orders', $id);
            if ((float) DB::table('purchase_order_lines')->where('purchase_order_id', $id)->whereNull('deleted_at')->sum('received_qty') > 0) {
                throw ValidationException::withMessages(['status' => ['Received purchase orders cannot be cancelled.']]);
            }

            $this->updateStatus('purchase_orders', $id, $order->status, 'cancelled', [
                'cancelled_by' => $this->support->userId(),
                'cancelled_at' => now(),
            ], $reason);

            return $this->findOrder($id);
        });
    }

    public function deleteOrder(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $order = $this->lockedRow('purchase_orders', $id);
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only draft purchase orders can be deleted.']]);
            }
            DB::table('purchase_orders')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
            DB::table('purchase_order_lines')->where('purchase_order_id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateGrns(array $filters): LengthAwarePaginator
    {
        $tenantId = $this->support->tenantId();
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('grn_headers')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'grn_headers.supplier_id')
            ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'grn_headers.purchase_order_id')
            ->select(['grn_headers.*', 'suppliers.supplier_name', 'purchase_orders.po_number'])
            ->where('grn_headers.tenant_id', $tenantId)
            ->whereNull('grn_headers.deleted_at')
            ->when(isset($filters['status']), fn (Builder $query): Builder => $query->where('grn_headers.status', (string) $filters['status']))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q
                ->where('grn_headers.grn_number', 'like', "%$search%")
                ->orWhere('purchase_orders.po_number', 'like', "%$search%")
                ->orWhere('suppliers.supplier_name', 'like', "%$search%")))
            ->orderByDesc('grn_headers.received_date')
            ->orderByDesc('grn_headers.id')
            ->paginate(min((int) ($filters['per_page'] ?? 20), 200), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function findGrn(int $id): object
    {
        $grn = DB::table('grn_headers')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'grn_headers.supplier_id')
            ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'grn_headers.purchase_order_id')
            ->select(['grn_headers.*', 'suppliers.supplier_name', 'purchase_orders.po_number'])
            ->where('grn_headers.tenant_id', $this->support->tenantId())
            ->whereNull('grn_headers.deleted_at')
            ->where('grn_headers.id', $id)
            ->first();

        if ($grn === null) {
            abort(404);
        }

        $grn->lines = $this->grnLines($id);
        $grn->invoice_links = DB::table('purchase_invoice_links')
            ->join('invoices', 'invoices.id', '=', 'purchase_invoice_links.invoice_id')
            ->select(['purchase_invoice_links.id', 'purchase_invoice_links.invoice_id', 'purchase_invoice_links.linked_amount', 'invoices.invoice_number', 'invoices.status', 'invoices.grand_total', 'invoices.balance_total'])
            ->where('purchase_invoice_links.tenant_id', $this->support->tenantId())
            ->where('purchase_invoice_links.source_type', 'grn')
            ->where('purchase_invoice_links.source_id', $id)
            ->where('purchase_invoice_links.status', 'active')
            ->get();

        return $grn;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createGrn(array $payload): object
    {
        return $this->storeGrn($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateGrn(int $id, array $payload): object
    {
        return DB::transaction(function () use ($id, $payload): object {
            $grn = $this->lockedRow('grn_headers', $id);
            if ($grn->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only draft GRNs can be edited.']]);
            }

            DB::table('grn_lines')->where('grn_header_id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);

            return $this->storeGrn(array_merge((array) $grn, $payload), $id);
        });
    }

    public function postGrn(int $id): object
    {
        return DB::transaction(function () use ($id): object {
            $tenantId = $this->support->tenantId();
            $grn = $this->lockedRow('grn_headers', $id);
            if ($grn->status === 'posted' || $grn->status === 'invoiced') {
                return $this->findGrn($id);
            }
            if (! in_array($grn->status, ['draft', 'confirmed'], true)) {
                throw ValidationException::withMessages(['status' => ['Only draft or confirmed GRNs can be posted.']]);
            }

            $lines = $this->grnLines($id);
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => ['At least one GRN line is required.']]);
            }

            $this->stockReceiving->receive([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $grn->organization_unit_id,
                'source_module' => 'purchase',
                'source_type' => 'purchase_grn',
                'source_id' => $id,
                'source_reference' => $grn->grn_number,
                'warehouse_id' => (int) $grn->warehouse_id,
                'lines' => $lines->map(fn (object $line): array => [
                    'source_line_id' => (int) $line->id,
                    'item_id' => (int) $line->item_id,
                    'uom_id' => (int) $line->uom_id,
                    'warehouse_id' => (int) ($line->warehouse_id ?? $grn->warehouse_id),
                    'location_id' => $line->location_id,
                    'variant_id' => $line->variant_id,
                    'batch_id' => $line->batch_id,
                    'serial_id' => $line->serial_id,
                    'quantity' => (float) $line->accepted_qty,
                    'unit_cost' => (float) $line->unit_price,
                ])->all(),
            ]);

            DB::table('grn_headers')->where('id', $id)->update([
                'status' => 'posted',
                'posted_by' => $this->support->userId(),
                'posted_at' => now(),
                'updated_at' => now(),
                'row_version' => ((int) $grn->row_version) + 1,
            ]);
            $this->recordStatus('grn_header', $id, $grn->status, 'posted');

            foreach ($lines as $line) {
                if ($line->purchase_order_line_id !== null) {
                    DB::table('purchase_order_lines')
                        ->where('id', (int) $line->purchase_order_line_id)
                        ->increment('received_qty', (float) $line->accepted_qty, ['updated_at' => now()]);
                }
            }
            if ($grn->purchase_order_id !== null) {
                $this->refreshOrderReceiptStatus((int) $grn->purchase_order_id);
            }

            return $this->findGrn($id);
        });
    }

    public function deleteGrn(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $grn = $this->lockedRow('grn_headers', $id);
            if ($grn->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only draft GRNs can be deleted.']]);
            }
            DB::table('grn_headers')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
            DB::table('grn_lines')->where('grn_header_id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function createInvoiceFromOrder(int $id, array $options = []): object
    {
        return DB::transaction(function () use ($id, $options): object {
            $order = $this->lockedRow('purchase_orders', $id);
            if (in_array($order->status, ['draft', 'cancelled'], true)) {
                throw ValidationException::withMessages(['status' => ['Only confirmed or received purchase orders can be invoiced.']]);
            }

            $entries = $this->invoiceEntriesForOrder($order, $options);
            $invoice = $this->createPurchaseInvoiceFromEntries('purchase_order', $order, $entries, $options);
            $this->refreshOrderInvoiceStatus($id);

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function createInvoiceFromGrn(int $id, array $options = []): object
    {
        return DB::transaction(function () use ($id, $options): object {
            $grn = $this->lockedRow('grn_headers', $id);
            if ($grn->status !== 'posted') {
                throw ValidationException::withMessages(['status' => ['Only posted GRNs can be invoiced.']]);
            }

            $entries = $this->invoiceEntriesForGrn($grn, $options);
            $invoice = $this->createPurchaseInvoiceFromEntries('grn', $grn, $entries, $options);
            $this->refreshGrnInvoiceStatus($id);
            if ($grn->purchase_order_id !== null) {
                $this->refreshOrderInvoiceStatus((int) $grn->purchase_order_id);
            }

            return $invoice;
        });
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, array<string, mixed>>
     */
    private function invoiceEntriesForOrder(object $order, array $options): Collection
    {
        $requested = $this->requestedInvoiceQuantities($options);
        $lines = DB::table('purchase_order_lines')
            ->where('tenant_id', $this->support->tenantId())
            ->where('purchase_order_id', (int) $order->id)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        return $this->buildInvoiceEntries($lines, $requested, 'ordered_qty', 'purchase_order_line_id');
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, array<string, mixed>>
     */
    private function invoiceEntriesForGrn(object $grn, array $options): Collection
    {
        $requested = $this->requestedInvoiceQuantities($options);
        $lines = DB::table('grn_lines')
            ->where('tenant_id', $this->support->tenantId())
            ->where('grn_header_id', (int) $grn->id)
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        return $this->buildInvoiceEntries($lines, $requested, 'received_qty', 'grn_line_id');
    }

    /**
     * @param  Collection<int, object>  $lines
     * @param  array<int, float>  $requested
     * @return Collection<int, array<string, mixed>>
     */
    private function buildInvoiceEntries(Collection $lines, array $requested, string $sourceQuantityMode, string $errorKey): Collection
    {
        $entries = $lines
            ->map(function (object $line) use ($requested, $sourceQuantityMode, $errorKey): ?array {
                $sourceQuantity = $sourceQuantityMode === 'ordered_qty'
                    ? (float) $line->ordered_qty
                    : max(0, (float) $line->accepted_qty - (float) $line->returned_qty);
                $remaining = round($sourceQuantity - (float) $line->invoiced_qty, 4);
                if ($remaining <= 0) {
                    return null;
                }
                if ($requested !== [] && ! array_key_exists((int) $line->id, $requested)) {
                    return null;
                }
                $quantity = $requested[(int) $line->id] ?? $remaining;
                if ($quantity <= 0 || $quantity > $remaining + 0.0001) {
                    throw ValidationException::withMessages([$errorKey => ['Invoice quantity cannot exceed remaining source quantity.']]);
                }
                $ratio = $this->quantityRatio((float) $quantity, $sourceQuantity);

                return [
                    'line' => $line,
                    'quantity' => round((float) $quantity, 4),
                    'remaining_quantity' => $remaining,
                    'source_quantity' => round($sourceQuantity, 4),
                    'source_amount' => round($sourceQuantity * (float) $line->unit_price, 4),
                    'linked_amount' => round((float) $quantity * (float) $line->unit_price, 4),
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
     * @param  array<string, mixed>  $options
     * @return array<int, float>
     */
    private function requestedInvoiceQuantities(array $options): array
    {
        $requested = [];
        foreach (array_values($options['lines'] ?? []) as $line) {
            $requested[(int) $line['source_line_id']] = (float) $line['quantity'];
        }

        return $requested;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @param  array<string, mixed>  $options
     */
    private function createPurchaseInvoiceFromEntries(string $sourceType, object $source, Collection $entries, array $options): object
    {
        $tenantId = $this->support->tenantId();
        $supplier = DB::table('suppliers')->where('tenant_id', $tenantId)->where('id', (int) $source->supplier_id)->first();
        $headerAllocations = $this->sourceHeaderAllocations($sourceType, $source, $entries);
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
            'adjustments' => $this->invoiceHeaderAdjustmentsFromAmounts($headerAllocations['totals']),
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
            $header = $headerAllocations['lines'][$index] ?? [];
            DB::table('purchase_invoice_links')->insert([
                'tenant_id' => $tenantId,
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
                'allocated_header_discount_amount' => $header['header_discount_amount'] ?? 0,
                'allocated_line_tax_amount' => $entry['line_tax'],
                'allocated_header_tax_amount' => $header['header_tax_amount'] ?? 0,
                'allocated_charge_amount' => $header['charge_amount'] ?? 0,
                'allocated_debit_adjustment_amount' => $header['debit_adjustment_amount'] ?? 0,
                'allocated_credit_adjustment_amount' => $header['credit_adjustment_amount'] ?? 0,
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

    private function incrementInvoicedQuantity(string $sourceType, object $sourceLine, float $quantity): void
    {
        if ($sourceType === 'purchase_order') {
            DB::table('purchase_order_lines')->where('id', (int) $sourceLine->id)->increment('invoiced_qty', $quantity, ['updated_at' => now()]);

            return;
        }

        DB::table('grn_lines')->where('id', (int) $sourceLine->id)->increment('invoiced_qty', $quantity, ['updated_at' => now()]);
        if ($sourceLine->purchase_order_line_id !== null) {
            DB::table('purchase_order_lines')->where('id', (int) $sourceLine->purchase_order_line_id)->increment('invoiced_qty', $quantity, ['updated_at' => now()]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateReturns(array $filters): LengthAwarePaginator
    {
        $tenantId = $this->support->tenantId();
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('purchase_returns')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_returns.supplier_id')
            ->leftJoin('grn_headers', 'grn_headers.id', '=', 'purchase_returns.original_grn_id')
            ->select(['purchase_returns.*', 'suppliers.supplier_name', 'grn_headers.grn_number'])
            ->where('purchase_returns.tenant_id', $tenantId)
            ->whereNull('purchase_returns.deleted_at')
            ->when(isset($filters['status']), fn (Builder $query): Builder => $query->where('purchase_returns.status', (string) $filters['status']))
            ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q
                ->where('purchase_returns.return_number', 'like', "%$search%")
                ->orWhere('grn_headers.grn_number', 'like', "%$search%")
                ->orWhere('suppliers.supplier_name', 'like', "%$search%")))
            ->orderByDesc('purchase_returns.return_date')
            ->orderByDesc('purchase_returns.id')
            ->paginate(min((int) ($filters['per_page'] ?? 20), 200), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function findReturn(int $id): object
    {
        $return = DB::table('purchase_returns')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_returns.supplier_id')
            ->leftJoin('grn_headers', 'grn_headers.id', '=', 'purchase_returns.original_grn_id')
            ->select(['purchase_returns.*', 'suppliers.supplier_name', 'grn_headers.grn_number'])
            ->where('purchase_returns.tenant_id', $this->support->tenantId())
            ->whereNull('purchase_returns.deleted_at')
            ->where('purchase_returns.id', $id)
            ->first();

        if ($return === null) {
            abort(404);
        }

        $return->lines = $this->returnLines($id);
        $return->invoice_links = DB::table('purchase_invoice_links')
            ->join('invoices', 'invoices.id', '=', 'purchase_invoice_links.invoice_id')
            ->select(['purchase_invoice_links.id', 'purchase_invoice_links.invoice_id', 'purchase_invoice_links.linked_amount', 'invoices.invoice_number', 'invoices.status', 'invoices.grand_total', 'invoices.balance_total'])
            ->where('purchase_invoice_links.tenant_id', $this->support->tenantId())
            ->where('purchase_invoice_links.source_type', 'purchase_return')
            ->where('purchase_invoice_links.source_id', $id)
            ->where('purchase_invoice_links.status', 'active')
            ->get();

        return $return;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createReturn(array $payload): object
    {
        return $this->storeReturn($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateReturn(int $id, array $payload): object
    {
        return DB::transaction(function () use ($id, $payload): object {
            $return = $this->lockedRow('purchase_returns', $id);
            if ($return->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only draft purchase returns can be edited.']]);
            }

            DB::table('purchase_return_lines')->where('purchase_return_id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);

            return $this->storeReturn(array_merge((array) $return, $payload), $id);
        });
    }

    public function postReturn(int $id): object
    {
        return DB::transaction(function () use ($id): object {
            $tenantId = $this->support->tenantId();
            $return = $this->lockedRow('purchase_returns', $id);
            if ($return->status === 'posted' || $return->status === 'refunded') {
                return $this->findReturn($id);
            }
            if ($return->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only draft purchase returns can be posted.']]);
            }

            $lines = $this->returnLines($id);
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => ['At least one return line is required.']]);
            }

            $this->stockIssuing->issue([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $return->organization_unit_id,
                'source_module' => 'purchase',
                'source_type' => 'purchase_return',
                'source_id' => $id,
                'source_reference' => $return->return_number,
                'lines' => $lines->map(fn (object $line): array => [
                    'source_line_id' => (int) $line->id,
                    'item_id' => (int) $line->item_id,
                    'uom_id' => (int) $line->uom_id,
                    'warehouse_id' => (int) $line->warehouse_id,
                    'location_id' => $line->location_id,
                    'variant_id' => $line->variant_id,
                    'batch_id' => $line->batch_id,
                    'serial_id' => $line->serial_id,
                    'quantity' => (float) $line->return_qty,
                ])->all(),
            ]);

            foreach ($lines as $line) {
                if ($line->original_grn_line_id !== null) {
                    DB::table('grn_lines')->where('id', (int) $line->original_grn_line_id)->increment('returned_qty', (float) $line->return_qty, ['updated_at' => now()]);
                }
                if ($line->original_purchase_order_line_id !== null) {
                    DB::table('purchase_order_lines')->where('id', (int) $line->original_purchase_order_line_id)->increment('returned_qty', (float) $line->return_qty, ['updated_at' => now()]);
                }
            }

            $status = 'posted';
            if ($return->original_invoice_id !== null) {
                $credit = $this->createSupplierCreditNote($return, $lines);
                $status = 'refunded';
                DB::table('purchase_invoice_links')->insert([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $return->organization_unit_id,
                    'source_type' => 'purchase_return',
                    'source_id' => $id,
                    'invoice_id' => (int) $credit->id,
                    'linked_amount' => (float) $credit->grand_total,
                    'status' => 'active',
                    'linked_at' => now(),
                    'created_by' => $this->support->userId(),
                    'row_version' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('purchase_returns')->where('id', $id)->update([
                'status' => $status,
                'posted_by' => $this->support->userId(),
                'posted_at' => now(),
                'updated_at' => now(),
                'row_version' => ((int) $return->row_version) + 1,
            ]);
            $this->recordStatus('purchase_return', $id, $return->status, $status);

            return $this->findReturn($id);
        });
    }

    public function deleteReturn(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $return = $this->lockedRow('purchase_returns', $id);
            if ($return->status !== 'draft') {
                throw ValidationException::withMessages(['status' => ['Only draft purchase returns can be deleted.']]);
            }
            DB::table('purchase_returns')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
            DB::table('purchase_return_lines')->where('purchase_return_id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $tenantId = $this->support->tenantId();

        return [
            'total_purchase_orders' => DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereNull('deleted_at')->count(),
            'open_purchase_orders' => DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'pending_receive_count' => DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('status', 'confirmed')->count(),
            'partially_received_count' => DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('status', 'partially_received')->count(),
            'received_count' => DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('status', 'received')->count(),
            'pending_invoice_count' => DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereIn('invoice_status', ['not_invoiced', 'partially_invoiced'])->whereIn('status', ['partially_received', 'received', 'closed'])->count(),
            'pending_grns' => DB::table('grn_headers')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereIn('status', ['draft', 'confirmed'])->count(),
            'posted_grns' => DB::table('grn_headers')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('status', 'posted')->count(),
            'open_returns' => DB::table('purchase_returns')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereIn('status', ['draft', 'posted'])->count(),
            'purchase_order_total' => $this->money(DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereNotIn('status', ['cancelled'])->sum('grand_total')),
            'total_purchase_value' => $this->money(DB::table('purchase_orders')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereNotIn('status', ['cancelled'])->sum('grand_total')),
            'supplier_outstanding' => $this->money(DB::table('ap_transactions')->where('tenant_id', $tenantId)->where('status', 'OPEN')->sum('outstanding_amount')),
            'unpaid_purchase_invoices' => [
                'count' => DB::table('invoices')->where('tenant_id', $tenantId)->where('ledger_direction', 'payable')->whereNull('deleted_at')->where('balance_total', '>', 0)->count(),
                'amount' => $this->money(DB::table('invoices')->where('tenant_id', $tenantId)->where('ledger_direction', 'payable')->whereNull('deleted_at')->sum('balance_total')),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function lookup(string $type, array $filters = []): array
    {
        $tenantId = $this->support->tenantId();
        $search = trim((string) ($filters['search'] ?? ''));
        $limit = min((int) ($filters['limit'] ?? 50), 100);

        return match ($type) {
            'suppliers' => DB::table('suppliers')
                ->select(['id', 'supplier_code as code', 'supplier_name as name', 'payment_terms_days'])
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->where('supplier_code', 'like', "%$search%")->orWhere('supplier_name', 'like', "%$search%")))
                ->orderBy('supplier_name')
                ->limit($limit)
                ->get()
                ->map(fn (object $row): array => [...(array) $row, 'outstanding_balance' => $this->supplierOutstanding((int) $row->id)])
                ->all(),
            'items' => DB::table('items')
                ->select(['id', 'item_code as code', 'name', 'base_uom_id', 'purchase_uom_id', 'cost_price'])
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->where('item_code', 'like', "%$search%")->orWhere('name', 'like', "%$search%")->orWhere('sku', 'like', "%$search%")))
                ->orderBy('item_code')
                ->limit($limit)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all(),
            'warehouses' => DB::table('warehouses')
                ->select(['id', 'code', 'name'])
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->where('code', 'like', "%$search%")->orWhere('name', 'like', "%$search%")))
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all(),
            'warehouse-locations' => DB::table('warehouse_locations')
                ->select(['id', 'warehouse_id', 'code', 'name'])
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->when(isset($filters['warehouse_id']), fn (Builder $query): Builder => $query->where('warehouse_id', (int) $filters['warehouse_id']))
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all(),
            'uoms' => DB::table('unit_of_measures')
                ->select(['id', 'uom_code as code', 'name', 'symbol'])
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->orderBy('uom_code')
                ->limit($limit)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all(),
            'open-purchase-orders' => DB::table('purchase_orders')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
                ->select(['purchase_orders.id', 'purchase_orders.po_number as code', 'suppliers.supplier_name as name', 'purchase_orders.supplier_id', 'purchase_orders.warehouse_id', 'purchase_orders.status'])
                ->where('purchase_orders.tenant_id', $tenantId)
                ->whereNull('purchase_orders.deleted_at')
                ->whereIn('purchase_orders.status', ['confirmed', 'partially_received'])
                ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->where('purchase_orders.po_number', 'like', "%$search%")->orWhere('suppliers.supplier_name', 'like', "%$search%")))
                ->orderByDesc('purchase_orders.order_date')
                ->limit($limit)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all(),
            'receivable-po-lines' => $this->receivablePoLineLookup($filters, $limit),
            'received-grns' => DB::table('grn_headers')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'grn_headers.supplier_id')
                ->select(['grn_headers.id', 'grn_headers.grn_number as code', 'suppliers.supplier_name as name', 'grn_headers.supplier_id', 'grn_headers.warehouse_id', 'grn_headers.status'])
                ->where('grn_headers.tenant_id', $tenantId)
                ->whereNull('grn_headers.deleted_at')
                ->where('grn_headers.status', 'posted')
                ->whereIn('grn_headers.invoice_status', ['not_invoiced', 'partially_invoiced'])
                ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $q) => $q->where('grn_headers.grn_number', 'like', "%$search%")->orWhere('suppliers.supplier_name', 'like', "%$search%")))
                ->orderByDesc('grn_headers.received_date')
                ->limit($limit)
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all(),
            default => throw ValidationException::withMessages(['type' => ['Unsupported lookup type.']]),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeGrn(array $payload, ?int $id = null): object
    {
        return DB::transaction(function () use ($payload, $id): object {
            $tenantId = $this->support->tenantId();
            $po = null;
            if (! empty($payload['purchase_order_id'])) {
                $po = $this->lockedRow('purchase_orders', (int) $payload['purchase_order_id']);
                if (in_array($po->status, ['cancelled', 'closed'], true)) {
                    throw ValidationException::withMessages(['purchase_order_id' => ['The selected purchase order is not receivable.']]);
                }
            }
            if ($po === null) {
                throw ValidationException::withMessages(['purchase_order_id' => ['GRN must reference a purchase order.']]);
            }

            $organizationUnitId = $this->support->organizationUnitId($payload['organization_unit_id'] ?? $po?->organization_unit_id);
            $supplierId = (int) ($payload['supplier_id'] ?? $po?->supplier_id);
            $warehouseId = (int) ($payload['warehouse_id'] ?? $po?->warehouse_id);
            $this->support->assertTenantRow('suppliers', $supplierId, 'supplier_id');
            $this->support->assertTenantRow('warehouses', $warehouseId, 'warehouse_id');
            $lines = $this->normalizeGrnLines($payload, $po, $id);
            $headerPayload = $this->headerPayload($payload, $po, $lines, 'received_qty');
            $totals = $this->totals($lines, 'received_qty', false, $headerPayload);

            $attributes = [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'purchase_order_id' => $po?->id,
                'grn_number' => $payload['grn_number'] ?? $this->support->nextNumber('GRN', 'grn_headers', 'grn_number'),
                'reference' => $payload['reference'] ?? null,
                'status' => 'draft',
                'invoice_status' => 'not_invoiced',
                'received_date' => $payload['received_date'],
                'currency_id' => $payload['currency_id'] ?? $po?->currency_id,
                'exchange_rate' => $payload['exchange_rate'] ?? $po?->exchange_rate ?? 1,
                ...$totals,
                'notes' => $payload['notes'] ?? null,
                'updated_by' => $this->support->userId(),
                'updated_at' => now(),
            ];

            if ($id === null) {
                $id = DB::table('grn_headers')->insertGetId([
                    ...$attributes,
                    'created_by' => $this->support->userId(),
                    'row_version' => 1,
                    'created_at' => now(),
                ]);
                $this->recordStatus('grn_header', $id, null, 'draft');
            } else {
                $current = $this->lockedRow('grn_headers', $id);
                DB::table('grn_headers')->where('id', $id)->update([
                    ...$attributes,
                    'row_version' => ((int) $current->row_version) + 1,
                ]);
            }

            $this->insertGrnLines($id, $tenantId, $organizationUnitId, $warehouseId, $lines);

            return $this->findGrn($id);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeReturn(array $payload, ?int $id = null): object
    {
        return DB::transaction(function () use ($payload, $id): object {
            $tenantId = $this->support->tenantId();
            $grn = ! empty($payload['original_grn_id']) ? $this->lockedRow('grn_headers', (int) $payload['original_grn_id']) : null;
            if ($grn !== null && ! in_array($grn->status, ['posted', 'invoiced', 'partially_invoiced'], true)) {
                throw ValidationException::withMessages(['original_grn_id' => ['Only posted GRNs can be returned.']]);
            }

            $organizationUnitId = $this->support->organizationUnitId($payload['organization_unit_id'] ?? $grn?->organization_unit_id);
            $supplierId = (int) ($payload['supplier_id'] ?? $grn?->supplier_id);
            $this->support->assertTenantRow('suppliers', $supplierId, 'supplier_id');
            if (! empty($payload['original_invoice_id'])) {
                $this->support->assertTenantRow('invoices', (int) $payload['original_invoice_id'], 'original_invoice_id');
            }

            $lines = $this->normalizeReturnLines($payload, $grn, $id);
            $totals = $this->totals($lines, 'return_qty', true, $payload);

            $attributes = [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'supplier_id' => $supplierId,
                'original_purchase_order_id' => $payload['original_purchase_order_id'] ?? $grn?->purchase_order_id,
                'original_grn_id' => $grn?->id,
                'original_invoice_id' => $payload['original_invoice_id'] ?? null,
                'return_number' => $payload['return_number'] ?? $this->support->nextNumber('PR', 'purchase_returns', 'return_number'),
                'reference' => $payload['reference'] ?? null,
                'status' => 'draft',
                'return_date' => $payload['return_date'],
                'return_reason' => $payload['return_reason'] ?? null,
                'is_without_original' => $grn === null,
                'currency_id' => $payload['currency_id'] ?? $grn?->currency_id,
                'exchange_rate' => $payload['exchange_rate'] ?? $grn?->exchange_rate ?? 1,
                ...$totals,
                'notes' => $payload['notes'] ?? null,
                'updated_by' => $this->support->userId(),
                'updated_at' => now(),
            ];

            if ($id === null) {
                $id = DB::table('purchase_returns')->insertGetId([
                    ...$attributes,
                    'created_by' => $this->support->userId(),
                    'row_version' => 1,
                    'created_at' => now(),
                ]);
                $this->recordStatus('purchase_return', $id, null, 'draft');
            } else {
                $current = $this->lockedRow('purchase_returns', $id);
                DB::table('purchase_returns')->where('id', $id)->update([
                    ...$attributes,
                    'row_version' => ((int) $current->row_version) + 1,
                ]);
            }

            $this->insertReturnLines($id, $tenantId, $organizationUnitId, $lines);

            return $this->findReturn($id);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateOrderHeader(array $payload): void
    {
        $this->support->assertTenantRow('suppliers', (int) $payload['supplier_id'], 'supplier_id');
        $this->support->assertTenantRow('warehouses', (int) $payload['warehouse_id'], 'warehouse_id');
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function validateLines(array $lines): void
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => ['At least one line is required.']]);
        }
        foreach ($lines as $index => $line) {
            if ((float) ($line['ordered_qty'] ?? $line['received_qty'] ?? $line['return_qty'] ?? 0) <= 0) {
                throw ValidationException::withMessages(["lines.$index.quantity" => ['Quantity must be greater than zero.']]);
            }
            $this->support->assertTenantRow('items', (int) $line['item_id'], "lines.$index.item_id");
            $this->support->assertTenantRow('unit_of_measures', (int) $line['uom_id'], "lines.$index.uom_id");
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function insertOrderLines(int $orderId, int $tenantId, ?int $organizationUnitId, array $lines): void
    {
        foreach (array_values($lines) as $line) {
            DB::table('purchase_order_lines')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'purchase_order_id' => $orderId,
                ...$this->lineAttributes($line, 'ordered_qty'),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function insertGrnLines(int $grnId, int $tenantId, ?int $organizationUnitId, int $warehouseId, array $lines): void
    {
        foreach (array_values($lines) as $line) {
            DB::table('grn_lines')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'grn_header_id' => $grnId,
                'purchase_order_line_id' => $line['purchase_order_line_id'] ?? null,
                'expected_qty' => $line['expected_qty'] ?? $line['received_qty'],
                'accepted_qty' => $line['accepted_qty'] ?? $line['received_qty'],
                'warehouse_id' => $line['warehouse_id'] ?? $warehouseId,
                'location_id' => $line['location_id'] ?? null,
                'batch_id' => $line['batch_id'] ?? null,
                'serial_id' => $line['serial_id'] ?? null,
                ...$this->lineAttributes($line, 'received_qty'),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function insertReturnLines(int $returnId, int $tenantId, ?int $organizationUnitId, array $lines): void
    {
        foreach (array_values($lines) as $line) {
            DB::table('purchase_return_lines')->insert([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'purchase_return_id' => $returnId,
                'original_grn_line_id' => $line['original_grn_line_id'] ?? null,
                'original_purchase_order_line_id' => $line['original_purchase_order_line_id'] ?? null,
                'original_invoice_line_id' => $line['original_invoice_line_id'] ?? null,
                'warehouse_id' => $line['warehouse_id'] ?? null,
                'location_id' => $line['location_id'] ?? null,
                'batch_id' => $line['batch_id'] ?? null,
                'serial_id' => $line['serial_id'] ?? null,
                'restocking_fee' => $line['restocking_fee'] ?? 0,
                'condition' => $line['condition'] ?? 'good',
                'disposition' => $line['disposition'] ?? 'return_to_vendor',
                ...$this->lineAttributes($line, 'return_qty'),
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function lineAttributes(array $line, string $quantityKey): array
    {
        $quantity = (float) $line[$quantityKey];
        $unitPrice = (float) ($line['unit_price'] ?? $line['unit_cost'] ?? 0);
        $gross = round($quantity * $unitPrice, 4);
        $discount = $this->discountAmount($gross, (string) ($line['discount_type'] ?? ''), (float) ($line['discount_value'] ?? 0), (float) ($line['discount_amount'] ?? 0));
        $tax = round((float) ($line['tax_amount'] ?? 0), 4);
        $lineTotal = round(max(0, $gross - $discount), 4);

        return [
            'item_id' => (int) $line['item_id'],
            'variant_id' => $line['variant_id'] ?? null,
            'description' => $line['description'] ?? null,
            'uom_id' => (int) $line['uom_id'],
            $quantityKey => $quantity,
            'unit_price' => $unitPrice,
            'discount_type' => $line['discount_type'] ?? null,
            'discount_value' => $line['discount_value'] ?? 0,
            'discount_amount' => $discount,
            'gross_amount' => $gross,
            'line_total' => $lineTotal,
            'tax_group_id' => $line['tax_group_id'] ?? null,
            'tax_amount' => $tax,
            'line_total_with_tax' => round($lineTotal + $tax, 4),
            'account_id' => $line['account_id'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, float>
     */
    private function totals(array $lines, string $quantityKey, bool $includeRestocking = false, array $header = []): array
    {
        $subtotal = $discount = $tax = $restocking = 0.0;
        foreach ($lines as $line) {
            $attrs = $this->lineAttributes($line, $quantityKey);
            $subtotal += (float) $attrs['gross_amount'];
            $discount += (float) $attrs['discount_amount'];
            $tax += (float) $attrs['tax_amount'];
            $restocking += $includeRestocking ? (float) ($line['restocking_fee'] ?? 0) : 0;
        }
        $headerBase = max(0, $subtotal - $discount);
        $headerDiscount = $this->discountAmount(
            $headerBase,
            (string) ($header['header_discount_type'] ?? ''),
            (float) ($header['header_discount_value'] ?? 0),
            (float) ($header['header_discount_amount'] ?? 0)
        );
        $headerTax = round((float) ($header['header_tax_amount'] ?? 0), 4);
        $debitNotes = round($this->firstAmount($header, ['debit_note_total', 'header_charge_total', 'header_debit_adjustment_total']), 4);
        $creditNotes = round($this->firstAmount($header, ['credit_note_total', 'header_credit_adjustment_total']), 4);

        $totals = [
            'subtotal' => round($subtotal, 4),
            'line_tax_total' => round($tax, 4),
            'line_discount_total' => round($discount, 4),
            'header_discount_type' => $header['header_discount_type'] ?? null,
            'header_discount_value' => isset($header['header_discount_value']) ? round((float) $header['header_discount_value'], 4) : null,
            'header_discount_amount' => $headerDiscount,
            'header_tax_group_id' => $header['header_tax_group_id'] ?? null,
            'header_tax_amount' => $headerTax,
            'discount_total' => round($discount + $headerDiscount, 4),
            'tax_total' => round($tax + $headerTax, 4),
            'debit_note_total' => $debitNotes,
            'credit_note_total' => $creditNotes,
            'grand_total' => round(max(0, $subtotal - $discount - $headerDiscount + $tax + $headerTax + $debitNotes - $creditNotes - $restocking), 4),
        ];

        if ($includeRestocking) {
            $totals['line_restocking_total'] = round($restocking, 4);
        }

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function firstAmount(array $payload, array $keys): float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return max(0, (float) $payload[$key]);
            }
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function headerPayload(array $payload, ?object $source, array $lines, string $quantityKey): array
    {
        $keys = ['header_discount_type', 'header_discount_value', 'header_discount_amount', 'header_tax_group_id', 'header_tax_amount', 'debit_note_total', 'credit_note_total', 'header_charge_total', 'header_debit_adjustment_total', 'header_credit_adjustment_total'];
        $header = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                $header[$key] = $payload[$key];
            }
        }
        if ($header !== [] || $source === null) {
            return $header;
        }

        $lineSubtotal = array_reduce($lines, fn (float $sum, array $line): float => $sum + round((float) $line[$quantityKey] * (float) ($line['unit_price'] ?? $line['unit_cost'] ?? 0), 4), 0.0);
        $sourceSubtotal = max(0.0001, (float) ($source->subtotal ?? $lineSubtotal));
        $ratio = min(1.0, max(0.0, $lineSubtotal / $sourceSubtotal));

        return [
            'header_discount_type' => null,
            'header_discount_value' => null,
            'header_discount_amount' => round((float) ($source->header_discount_amount ?? 0) * $ratio, 4),
            'header_tax_group_id' => $source->header_tax_group_id ?? null,
            'header_tax_amount' => round((float) ($source->header_tax_amount ?? 0) * $ratio, 4),
            'debit_note_total' => round((float) ($source->debit_note_total ?? 0) * $ratio, 4),
            'credit_note_total' => round((float) ($source->credit_note_total ?? 0) * $ratio, 4),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function invoiceHeaderAdjustments(object $document): array
    {
        return collect([
            ['adjustment_type' => 'discount', 'effect' => 'deduct', 'amount' => (float) ($document->header_discount_amount ?? 0), 'name' => 'Header discount'],
            ['adjustment_type' => 'tax', 'effect' => 'add', 'amount' => (float) ($document->header_tax_amount ?? 0), 'name' => 'Header tax'],
            ['adjustment_type' => 'charge', 'effect' => 'add', 'amount' => (float) ($document->debit_note_total ?? 0), 'name' => 'Header charge / debit adjustment'],
            ['adjustment_type' => 'credit_adjustment', 'effect' => 'deduct', 'amount' => (float) ($document->credit_note_total ?? 0), 'name' => 'Header credit adjustment'],
        ])
            ->filter(fn (array $adjustment): bool => (float) $adjustment['amount'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, float>  $amounts
     * @return array<int, array<string, mixed>>
     */
    private function invoiceHeaderAdjustmentsFromAmounts(array $amounts): array
    {
        return collect([
            ['adjustment_type' => 'discount', 'effect' => 'deduct', 'amount' => $amounts['header_discount_amount'] ?? 0, 'name' => 'Header discount'],
            ['adjustment_type' => 'tax', 'effect' => 'add', 'amount' => $amounts['header_tax_amount'] ?? 0, 'name' => 'Header tax'],
            ['adjustment_type' => 'charge', 'effect' => 'add', 'amount' => $amounts['charge_amount'] ?? 0, 'name' => 'Header charge'],
            ['adjustment_type' => 'debit_adjustment', 'effect' => 'add', 'amount' => $amounts['debit_adjustment_amount'] ?? 0, 'name' => 'Debit adjustment'],
            ['adjustment_type' => 'credit_adjustment', 'effect' => 'deduct', 'amount' => $amounts['credit_adjustment_amount'] ?? 0, 'name' => 'Credit adjustment'],
        ])
            ->filter(fn (array $adjustment): bool => (float) $adjustment['amount'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array{totals: array<string, float>, lines: array<int, array<string, float>>}
     */
    private function sourceHeaderAllocations(string $sourceType, object $source, Collection $entries): array
    {
        $currentGross = round((float) $entries->sum('linked_amount'), 4);
        $remainingGross = $this->sourceRemainingGross($sourceType, (int) $source->id);
        $isFinalAllocation = $currentGross + 0.0001 >= $remainingGross;
        $ratio = $remainingGross > 0 ? min(1.0, max(0.0, $currentGross / $remainingGross)) : 0.0;
        $remaining = [
            'header_discount_amount' => $this->remainingSourceAllocation($sourceType, (int) $source->id, (float) ($source->header_discount_amount ?? 0), 'allocated_header_discount_amount'),
            'header_tax_amount' => $this->remainingSourceAllocation($sourceType, (int) $source->id, (float) ($source->header_tax_amount ?? 0), 'allocated_header_tax_amount'),
            'charge_amount' => $this->remainingSourceAllocation($sourceType, (int) $source->id, (float) ($source->debit_note_total ?? 0), 'allocated_charge_amount'),
            'debit_adjustment_amount' => 0.0,
            'credit_adjustment_amount' => $this->remainingSourceAllocation($sourceType, (int) $source->id, (float) ($source->credit_note_total ?? 0), 'allocated_credit_adjustment_amount'),
        ];
        $totals = [];
        foreach ($remaining as $key => $amount) {
            $totals[$key] = $isFinalAllocation ? round($amount, 4) : round($amount * $ratio, 4);
        }

        $weights = $entries->map(fn (array $entry): float => (float) $entry['linked_amount'])->all();
        $lines = [];
        foreach ($totals as $key => $amount) {
            foreach ($this->allocateByWeights($amount, $weights) as $index => $allocation) {
                $lines[$index][$key] = $allocation;
            }
        }

        return ['totals' => $totals, 'lines' => $lines];
    }

    private function sourceRemainingGross(string $sourceType, int $sourceId): float
    {
        $query = $sourceType === 'purchase_order'
            ? DB::table('purchase_order_lines')
                ->where('purchase_order_id', $sourceId)
                ->selectRaw('coalesce(sum((ordered_qty - invoiced_qty) * unit_price), 0) as remaining_gross')
            : DB::table('grn_lines')
                ->where('grn_header_id', $sourceId)
                ->selectRaw('coalesce(sum((accepted_qty - returned_qty - invoiced_qty) * unit_price), 0) as remaining_gross');

        $row = $query
            ->where('tenant_id', $this->support->tenantId())
            ->whereNull('deleted_at')
            ->first();

        return round(max(0, (float) ($row->remaining_gross ?? 0)), 4);
    }

    private function remainingSourceAllocation(string $sourceType, int $sourceId, float $originalAmount, string $column): float
    {
        $allocated = (float) DB::table('purchase_invoice_links')
            ->where('tenant_id', $this->support->tenantId())
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'active')
            ->sum($column);

        return round(max(0, $originalAmount - $allocated), 4);
    }

    /**
     * @param  array<int, float>  $weights
     * @return array<int, float>
     */
    private function allocateByWeights(float $amount, array $weights): array
    {
        $amount = round($amount, 4);
        $totalWeight = array_sum($weights);
        if ($amount <= 0 || $totalWeight <= 0) {
            return array_fill(0, count($weights), 0.0);
        }

        $allocated = [];
        $running = 0.0;
        $lastIndex = count($weights) - 1;
        foreach (array_values($weights) as $index => $weight) {
            if ($index === $lastIndex) {
                $allocated[$index] = round($amount - $running, 4);
                break;
            }
            $share = round($amount * ($weight / $totalWeight), 4);
            $allocated[$index] = $share;
            $running += $share;
        }

        return $allocated;
    }

    private function discountAmount(float $gross, string $type, float $value, float $explicit): float
    {
        if ($type === 'percentage') {
            return round($gross * ($value / 100), 4);
        }
        if ($type === 'fixed') {
            return round(min($gross, $value), 4);
        }

        return round(min($gross, $explicit), 4);
    }

    private function quantityRatio(float $quantity, float $sourceQuantity): float
    {
        if ($sourceQuantity <= 0) {
            return 0.0;
        }

        return min(1.0, max(0.0, $quantity / $sourceQuantity));
    }

    private function transitionOrder(int $id, array $fromStatuses, string $toStatus, array $extra = []): object
    {
        return DB::transaction(function () use ($id, $fromStatuses, $toStatus, $extra): object {
            $order = $this->lockedRow('purchase_orders', $id);
            if (! in_array($order->status, $fromStatuses, true)) {
                throw ValidationException::withMessages(['status' => ['Purchase order cannot move to '.$toStatus.' from '.$order->status.'.']]);
            }
            $this->updateStatus('purchase_orders', $id, $order->status, $toStatus, $extra);

            return $this->findOrder($id);
        });
    }

    private function updateStatus(string $table, int $id, string $from, string $to, array $extra = [], ?string $reason = null): void
    {
        DB::table($table)->where('id', $id)->update([
            'status' => $to,
            'updated_at' => now(),
            ...$extra,
        ]);
        $entityType = match ($table) {
            'purchase_orders' => 'purchase_order',
            'grn_headers' => 'grn_header',
            'purchase_returns' => 'purchase_return',
            default => $table,
        };
        $this->recordStatus($entityType, $id, $from, $to, $reason);
    }

    private function recordStatus(string $entityType, int $entityId, ?string $from, string $to, ?string $reason = null): void
    {
        DB::table('purchase_status_histories')->insert([
            'tenant_id' => $this->support->tenantId(),
            'organization_unit_id' => $this->support->organizationUnitId(null),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'changed_by' => $this->support->userId(),
            'changed_at' => now(),
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function orderLines(int $orderId): Collection
    {
        return DB::table('purchase_order_lines')
            ->leftJoin('items', 'items.id', '=', 'purchase_order_lines.item_id')
            ->leftJoin('unit_of_measures', 'unit_of_measures.id', '=', 'purchase_order_lines.uom_id')
            ->select(['purchase_order_lines.*', 'items.item_code', 'items.name as item_name', 'unit_of_measures.uom_code'])
            ->where('purchase_order_lines.purchase_order_id', $orderId)
            ->whereNull('purchase_order_lines.deleted_at')
            ->orderBy('purchase_order_lines.id')
            ->get();
    }

    private function grnLines(int $grnId): Collection
    {
        return DB::table('grn_lines')
            ->leftJoin('items', 'items.id', '=', 'grn_lines.item_id')
            ->leftJoin('unit_of_measures', 'unit_of_measures.id', '=', 'grn_lines.uom_id')
            ->select(['grn_lines.*', 'items.item_code', 'items.name as item_name', 'unit_of_measures.uom_code'])
            ->where('grn_lines.grn_header_id', $grnId)
            ->whereNull('grn_lines.deleted_at')
            ->orderBy('grn_lines.id')
            ->get();
    }

    private function returnLines(int $returnId): Collection
    {
        return DB::table('purchase_return_lines')
            ->leftJoin('items', 'items.id', '=', 'purchase_return_lines.item_id')
            ->leftJoin('unit_of_measures', 'unit_of_measures.id', '=', 'purchase_return_lines.uom_id')
            ->select(['purchase_return_lines.*', 'items.item_code', 'items.name as item_name', 'unit_of_measures.uom_code'])
            ->where('purchase_return_lines.purchase_return_id', $returnId)
            ->whereNull('purchase_return_lines.deleted_at')
            ->orderBy('purchase_return_lines.id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function normalizeGrnLines(array $payload, ?object $po, ?int $currentGrnId): array
    {
        $lines = array_values($payload['lines'] ?? []);
        if ($lines === [] && $po !== null) {
            $lines = $this->orderLines((int) $po->id)
                ->map(fn (object $line): array => [
                    'purchase_order_line_id' => (int) $line->id,
                    'item_id' => (int) $line->item_id,
                    'uom_id' => (int) $line->uom_id,
                    'description' => $line->description,
                    'received_qty' => round((float) $line->ordered_qty - $this->grnCommittedQuantity((int) $line->id, $currentGrnId), 4),
                    'unit_price' => (float) $line->unit_price,
                    'discount_type' => $line->discount_type,
                    'discount_value' => (float) $line->discount_value,
                    'tax_amount' => (float) $line->tax_amount,
                ])
                ->filter(fn (array $line): bool => (float) $line['received_qty'] > 0)
                ->values()
                ->all();
        }
        $this->validateLines($lines);

        return array_map(function (array $line) use ($po, $currentGrnId): array {
            if ($po !== null) {
                $poLineId = (int) ($line['purchase_order_line_id'] ?? 0);
                $poLine = DB::table('purchase_order_lines')
                    ->where('tenant_id', $this->support->tenantId())
                    ->where('purchase_order_id', (int) $po->id)
                    ->where('id', $poLineId)
                    ->whereNull('deleted_at')
                    ->first();
                if ($poLine === null) {
                    throw ValidationException::withMessages(['purchase_order_line_id' => ['GRN line must reference a line on the selected purchase order.']]);
                }
                $remaining = round((float) $poLine->ordered_qty - $this->grnCommittedQuantity($poLineId, $currentGrnId), 4);
                if ((float) $line['received_qty'] > $remaining + 0.0001) {
                    throw ValidationException::withMessages(['received_qty' => ['Received quantity cannot exceed remaining PO quantity.']]);
                }
                $receivedQuantity = (float) $line['received_qty'];
                $acceptedQuantity = (float) ($line['accepted_qty'] ?? $line['received_qty']);
                $lineRatio = $this->quantityRatio($receivedQuantity, (float) $poLine->ordered_qty);
                $line = array_merge($line, [
                    'item_id' => (int) $poLine->item_id,
                    'variant_id' => $poLine->variant_id,
                    'description' => $line['description'] ?? $poLine->description,
                    'uom_id' => (int) $poLine->uom_id,
                    'received_qty' => $receivedQuantity,
                    'accepted_qty' => $acceptedQuantity,
                    'unit_price' => (float) $poLine->unit_price,
                    'discount_type' => $poLine->discount_type === 'percentage' ? 'percentage' : null,
                    'discount_value' => $poLine->discount_type === 'percentage' ? (float) $poLine->discount_value : 0,
                    'discount_amount' => round((float) $poLine->discount_amount * $lineRatio, 4),
                    'tax_group_id' => $poLine->tax_group_id,
                    'tax_amount' => round((float) $poLine->tax_amount * $lineRatio, 4),
                    'account_id' => $poLine->account_id,
                ]);
                $line['expected_qty'] = $remaining;
            }
            $line['accepted_qty'] = (float) ($line['accepted_qty'] ?? $line['received_qty']);
            if ((float) $line['accepted_qty'] <= 0 || (float) $line['accepted_qty'] > (float) $line['received_qty'] + 0.0001) {
                throw ValidationException::withMessages(['accepted_qty' => ['Accepted quantity must be positive and cannot exceed received quantity.']]);
            }

            return $line;
        }, $lines);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function normalizeReturnLines(array $payload, ?object $grn, ?int $currentReturnId): array
    {
        $lines = array_values($payload['lines'] ?? []);
        $this->validateLines($lines);

        return array_map(function (array $line) use ($grn, $currentReturnId): array {
            if ($grn !== null) {
                $grnLineId = (int) ($line['original_grn_line_id'] ?? 0);
                $grnLine = DB::table('grn_lines')
                    ->where('tenant_id', $this->support->tenantId())
                    ->where('grn_header_id', (int) $grn->id)
                    ->where('id', $grnLineId)
                    ->whereNull('deleted_at')
                    ->first();
                if ($grnLine === null) {
                    throw ValidationException::withMessages(['original_grn_line_id' => ['Return line must reference a line on the selected GRN.']]);
                }
                $remaining = round((float) $grnLine->accepted_qty - $this->returnCommittedQuantity($grnLineId, $currentReturnId), 4);
                if ((float) $line['return_qty'] > $remaining + 0.0001) {
                    throw ValidationException::withMessages(['return_qty' => ['Return quantity cannot exceed remaining received quantity.']]);
                }
                $returnQuantity = (float) $line['return_qty'];
                $lineRatio = $this->quantityRatio($returnQuantity, (float) $grnLine->accepted_qty);
                $line = array_merge($line, [
                    'item_id' => (int) $grnLine->item_id,
                    'variant_id' => $grnLine->variant_id,
                    'description' => $line['description'] ?? $grnLine->description,
                    'uom_id' => (int) $grnLine->uom_id,
                    'warehouse_id' => (int) ($grnLine->warehouse_id ?? $grn->warehouse_id),
                    'location_id' => $line['location_id'] ?? $grnLine->location_id,
                    'return_qty' => $returnQuantity,
                    'unit_price' => (float) $grnLine->unit_price,
                    'discount_type' => $grnLine->discount_type === 'percentage' ? 'percentage' : null,
                    'discount_value' => $grnLine->discount_type === 'percentage' ? (float) $grnLine->discount_value : 0,
                    'discount_amount' => round((float) $grnLine->discount_amount * $lineRatio, 4),
                    'tax_group_id' => $grnLine->tax_group_id,
                    'tax_amount' => round((float) $grnLine->tax_amount * $lineRatio, 4),
                    'account_id' => $grnLine->account_id,
                ]);
                $line['original_grn_line_id'] = $grnLineId;
                $line['original_purchase_order_line_id'] = $grnLine->purchase_order_line_id;
            }
            if (empty($line['warehouse_id'])) {
                throw ValidationException::withMessages(['warehouse_id' => ['Return line warehouse is required.']]);
            }

            return $line;
        }, $lines);
    }

    private function grnCommittedQuantity(int $poLineId, ?int $excludeGrnId = null): float
    {
        return (float) DB::table('grn_lines')
            ->join('grn_headers', 'grn_headers.id', '=', 'grn_lines.grn_header_id')
            ->where('grn_lines.purchase_order_line_id', $poLineId)
            ->whereNull('grn_lines.deleted_at')
            ->whereNull('grn_headers.deleted_at')
            ->whereNotIn('grn_headers.status', ['cancelled', 'reversed'])
            ->when($excludeGrnId !== null, fn (Builder $query): Builder => $query->where('grn_headers.id', '<>', $excludeGrnId))
            ->sum('grn_lines.received_qty');
    }

    private function returnCommittedQuantity(int $grnLineId, ?int $excludeReturnId = null): float
    {
        return (float) DB::table('purchase_return_lines')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_lines.purchase_return_id')
            ->where('purchase_return_lines.original_grn_line_id', $grnLineId)
            ->whereNull('purchase_return_lines.deleted_at')
            ->whereNull('purchase_returns.deleted_at')
            ->whereNotIn('purchase_returns.status', ['cancelled', 'reversed'])
            ->when($excludeReturnId !== null, fn (Builder $query): Builder => $query->where('purchase_returns.id', '<>', $excludeReturnId))
            ->sum('purchase_return_lines.return_qty');
    }

    private function refreshOrderReceiptStatus(int $orderId): void
    {
        $totals = DB::table('purchase_order_lines')
            ->selectRaw('coalesce(sum(ordered_qty), 0) as ordered, coalesce(sum(received_qty), 0) as received')
            ->where('purchase_order_id', $orderId)
            ->whereNull('deleted_at')
            ->first();
        $status = (float) $totals->received <= 0 ? 'confirmed' : ((float) $totals->received + 0.0001 >= (float) $totals->ordered ? 'received' : 'partially_received');
        $order = $this->lockedRow('purchase_orders', $orderId);
        if ($order->status !== $status) {
            $this->updateStatus('purchase_orders', $orderId, $order->status, $status);
        }
    }

    private function refreshGrnInvoiceStatus(int $grnId): void
    {
        $totals = DB::table('grn_lines')
            ->selectRaw('coalesce(sum(accepted_qty - returned_qty), 0) as receivable, coalesce(sum(invoiced_qty), 0) as invoiced')
            ->where('grn_header_id', $grnId)
            ->whereNull('deleted_at')
            ->first();
        $status = (float) $totals->invoiced + 0.0001 >= (float) $totals->receivable ? 'fully_invoiced' : 'partially_invoiced';
        DB::table('grn_headers')->where('id', $grnId)->update(['invoice_status' => $status, 'updated_at' => now()]);
    }

    private function refreshOrderInvoiceStatus(int $orderId): void
    {
        $totals = DB::table('purchase_order_lines')
            ->selectRaw('coalesce(sum(ordered_qty - returned_qty), 0) as receivable, coalesce(sum(invoiced_qty), 0) as invoiced')
            ->where('purchase_order_id', $orderId)
            ->whereNull('deleted_at')
            ->first();
        $status = (float) $totals->invoiced <= 0 ? 'not_invoiced' : ((float) $totals->invoiced + 0.0001 >= (float) $totals->receivable ? 'fully_invoiced' : 'partially_invoiced');
        DB::table('purchase_orders')->where('id', $orderId)->update(['invoice_status' => $status, 'updated_at' => now()]);
    }

    private function createSupplierCreditNote(object $return, Collection $lines): object
    {
        $credit = $this->invoices->create([
            'organization_unit_id' => $return->organization_unit_id,
            'external_reference_number' => $return->return_number,
            'document_type' => 'credit_adjustment',
            'business_context' => 'purchase_return',
            'ledger_direction' => 'payable',
            'balance_effect' => 'decrease',
            'supplier_id' => (int) $return->supplier_id,
            'original_invoice_id' => (int) $return->original_invoice_id,
            'invoice_date' => $return->return_date,
            'notes' => 'Generated from purchase return '.$return->return_number,
            'adjustments' => $this->invoiceHeaderAdjustments($return),
            'lines' => $lines->map(fn (object $line): array => [
                'item_id' => (int) $line->item_id,
                'uom_id' => (int) $line->uom_id,
                'description' => $line->description,
                'quantity' => (float) $line->return_qty,
                'unit_price' => (float) $line->unit_price,
                'discount_total' => (float) $line->discount_amount,
                'tax_total' => (float) $line->tax_amount,
                'charge_total' => 0,
            ])->all(),
        ]);

        return $this->invoices->issue((int) $credit->id);
    }

    private function supplierOutstanding(int $supplierId): string
    {
        return $this->money(DB::table('ap_transactions')
            ->where('tenant_id', $this->support->tenantId())
            ->where('party_type', 'supplier')
            ->where('party_id', $supplierId)
            ->where('status', 'OPEN')
            ->sum('outstanding_amount'));
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function receivablePoLineLookup(array $filters, int $limit): array
    {
        if (empty($filters['purchase_order_id'])) {
            return [];
        }

        return $this->orderLines((int) $filters['purchase_order_id'])
            ->filter(fn (object $line): bool => round((float) $line->ordered_qty - $this->grnCommittedQuantity((int) $line->id), 4) > 0)
            ->take($limit)
            ->map(fn (object $line): array => [
                'id' => (int) $line->id,
                'item_id' => (int) $line->item_id,
                'item_code' => $line->item_code,
                'name' => $line->item_name,
                'uom_id' => (int) $line->uom_id,
                'uom_code' => $line->uom_code,
                'ordered_qty' => $this->money($line->ordered_qty),
                'received_qty' => $this->money($line->received_qty),
                'remaining_qty' => $this->money(round((float) $line->ordered_qty - $this->grnCommittedQuantity((int) $line->id), 4)),
                'unit_price' => $this->money($line->unit_price),
            ])
            ->values()
            ->all();
    }
}
