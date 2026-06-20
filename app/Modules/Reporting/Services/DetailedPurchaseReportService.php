<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;

final class DetailedPurchaseReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly OperationalReportResponseBuilder $responses,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(array $params): array
    {
        $query = $this->query($params);
        $summary = $this->summary(clone $query);
        $this->sort($query, $params);

        return $this->responses->paginate(
            $query,
            fn (object $row): array => $this->row($row),
            $this->definition(),
            $summary,
            max(1, (int) ($params['page'] ?? 1)),
            min(100, max(1, (int) ($params['per_page'] ?? 25))),
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(array $params): Collection
    {
        $query = $this->query($params);
        $this->sort($query, $params);

        return $this->responses->exportRows($query, fn (object $row): array => $this->row($row));
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'purchase/detailed',
            title: 'Detailed Purchase Report',
            group: 'Purchase',
            model: PurchaseOrderLine::class,
            columns: [
                new ReportColumn('purchase_order_number', 'PO number', sortBy: 'purchase_order_number'),
                new ReportColumn('purchase_order_date', 'PO date', sortBy: 'purchase_order_date', format: 'date'),
                new ReportColumn('purchase_status', 'PO status', sortBy: 'purchase_status'),
                new ReportColumn('supplier', 'Supplier', sortBy: 'supplier'),
                new ReportColumn('warehouse', 'Warehouse'),
                new ReportColumn('line_number', 'Line', sortBy: 'line_number', format: 'decimal'),
                new ReportColumn('item_code', 'Item code', sortBy: 'item_code'),
                new ReportColumn('item_name', 'Item', sortBy: 'item_name'),
                new ReportColumn('variant', 'Variant'),
                new ReportColumn('uom', 'UOM'),
                new ReportColumn('ordered_quantity', 'Ordered qty', sortBy: 'ordered_quantity', format: 'decimal', summarize: true),
                new ReportColumn('received_quantity', 'Received qty', sortBy: 'received_quantity', format: 'decimal', summarize: true),
                new ReportColumn('invoiced_quantity', 'Invoiced qty', sortBy: 'invoiced_quantity', format: 'decimal', summarize: true),
                new ReportColumn('returned_quantity', 'Returned qty', sortBy: 'returned_quantity', format: 'decimal', summarize: true),
                new ReportColumn('remaining_receivable_quantity', 'Remaining receive', sortBy: 'remaining_receivable_quantity', format: 'decimal', summarize: true),
                new ReportColumn('remaining_invoiceable_quantity', 'Remaining invoice', sortBy: 'remaining_invoiceable_quantity', format: 'decimal', summarize: true),
                new ReportColumn('unit_price', 'Unit price', sortBy: 'unit_price', format: 'money'),
                new ReportColumn('line_subtotal', 'Subtotal', sortBy: 'line_subtotal', format: 'money', summarize: true),
                new ReportColumn('discount_amount', 'Discount', sortBy: 'discount_amount', format: 'money', summarize: true),
                new ReportColumn('tax_amount', 'Tax', sortBy: 'tax_amount', format: 'money', summarize: true),
                new ReportColumn('charge_amount', 'Charge', sortBy: 'charge_amount', format: 'money', summarize: true),
                new ReportColumn('line_total', 'Line total', sortBy: 'line_total', format: 'money', summarize: true),
                new ReportColumn('receipt_progress', 'Receipt progress'),
                new ReportColumn('invoice_progress', 'Invoice progress'),
            ],
            dateColumn: 'purchase_order_date',
            defaultSort: 'purchase_order_date',
            defaultDirection: 'desc',
            description: 'Line-level purchase report with supplier, item, quantities, fulfilment and amounts.',
            orientation: 'landscape',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function query(array $params): Builder
    {
        $query = DB::table('purchase_order_lines as lines')
            ->join('purchase_orders as orders', 'orders.id', '=', 'lines.purchase_order_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'orders.warehouse_id')
            ->leftJoin('items', 'items.id', '=', 'lines.item_id')
            ->leftJoin('item_variants as variants', 'variants.id', '=', 'lines.item_variant_id')
            ->leftJoin('unit_of_measures as uoms', 'uoms.id', '=', 'lines.uom_id')
            ->whereNull('orders.deleted_at')
            ->where('orders.tenant_id', (int) $params['tenant_id'])
            ->where('lines.tenant_id', (int) $params['tenant_id'])
            ->select([
                'lines.id',
                'orders.purchase_order_number',
                'orders.purchase_order_date',
                'orders.status as purchase_status',
                'orders.supplier_id',
                'orders.warehouse_id',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse_name',
                'lines.line_number',
                'lines.item_id',
                'items.code as item_code',
                'items.name as item_name',
                'variants.code as variant_code',
                'variants.name as variant_name',
                'uoms.code as uom_code',
                'lines.ordered_quantity',
                'lines.received_quantity',
                'lines.invoiced_quantity',
                'lines.returned_quantity',
                'lines.remaining_receivable_quantity',
                'lines.remaining_invoiceable_quantity',
                'lines.unit_price',
                'lines.line_subtotal',
                'lines.discount_amount',
                'lines.tax_amount',
                'lines.charge_amount',
                'lines.line_total',
            ]);

        $this->organizationScope($query, 'orders.organization_unit_id', $params['organization_unit_id'] ?? null);
        $this->organizationScope($query, 'lines.organization_unit_id', $params['organization_unit_id'] ?? null);

        if (! empty($params['date_from'])) {
            $query->whereDate('orders.purchase_order_date', '>=', (string) $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('orders.purchase_order_date', '<=', (string) $params['date_to']);
        }
        if (! empty($params['purchase_status'])) {
            $query->where('orders.status', (string) $params['purchase_status']);
        }
        if (! empty($params['supplier_id'])) {
            $query->where('orders.supplier_id', (int) $params['supplier_id']);
        }
        if (! empty($params['item_id'])) {
            $query->where('lines.item_id', (int) $params['item_id']);
        }

        $search = $this->searchTerm($params['search'] ?? null);
        if ($search !== null) {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('orders.purchase_order_number', 'like', $search)
                    ->orWhere('suppliers.code', 'like', $search)
                    ->orWhere('suppliers.name', 'like', $search)
                    ->orWhere('items.code', 'like', $search)
                    ->orWhere('items.name', 'like', $search)
                    ->orWhere('lines.description', 'like', $search);
            });
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Builder $query): array
    {
        $totals = $query->select([])->selectRaw(
            'COUNT(DISTINCT orders.id) as total_orders, COUNT(lines.id) as total_lines, '
            .'COALESCE(SUM(lines.ordered_quantity), 0) as ordered_quantity, '
            .'COALESCE(SUM(lines.received_quantity), 0) as received_quantity, '
            .'COALESCE(SUM(lines.invoiced_quantity), 0) as invoiced_quantity, '
            .'COALESCE(SUM(lines.returned_quantity), 0) as returned_quantity, '
            .'COALESCE(SUM(lines.remaining_receivable_quantity), 0) as remaining_receivable_quantity, '
            .'COALESCE(SUM(lines.remaining_invoiceable_quantity), 0) as remaining_invoiceable_quantity, '
            .'COALESCE(SUM(lines.line_subtotal), 0) as subtotal, '
            .'COALESCE(SUM(lines.discount_amount), 0) as discount_total, '
            .'COALESCE(SUM(lines.tax_amount), 0) as tax_total, '
            .'COALESCE(SUM(lines.charge_amount), 0) as charge_total, '
            .'COALESCE(SUM(lines.line_total), 0) as grand_total'
        )->first();

        return [
            'total_orders' => (int) ($totals->total_orders ?? 0),
            'total_lines' => (int) ($totals->total_lines ?? 0),
            'ordered_quantity' => $this->decimal($totals->ordered_quantity ?? 0),
            'received_quantity' => $this->decimal($totals->received_quantity ?? 0),
            'invoiced_quantity' => $this->decimal($totals->invoiced_quantity ?? 0),
            'returned_quantity' => $this->decimal($totals->returned_quantity ?? 0),
            'remaining_receivable_quantity' => $this->decimal($totals->remaining_receivable_quantity ?? 0),
            'remaining_invoiceable_quantity' => $this->decimal($totals->remaining_invoiceable_quantity ?? 0),
            'subtotal' => $this->decimal($totals->subtotal ?? 0),
            'discount_total' => $this->decimal($totals->discount_total ?? 0),
            'tax_total' => $this->decimal($totals->tax_total ?? 0),
            'charge_total' => $this->decimal($totals->charge_total ?? 0),
            'grand_total' => $this->decimal($totals->grand_total ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function sort(Builder $query, array $params): void
    {
        $columns = [
            'purchase_order_number' => 'orders.purchase_order_number',
            'purchase_order_date' => 'orders.purchase_order_date',
            'purchase_status' => 'orders.status',
            'supplier' => 'suppliers.name',
            'line_number' => 'lines.line_number',
            'item_code' => 'items.code',
            'item_name' => 'items.name',
            'ordered_quantity' => 'lines.ordered_quantity',
            'received_quantity' => 'lines.received_quantity',
            'invoiced_quantity' => 'lines.invoiced_quantity',
            'returned_quantity' => 'lines.returned_quantity',
            'remaining_receivable_quantity' => 'lines.remaining_receivable_quantity',
            'remaining_invoiceable_quantity' => 'lines.remaining_invoiceable_quantity',
            'unit_price' => 'lines.unit_price',
            'line_subtotal' => 'lines.line_subtotal',
            'discount_amount' => 'lines.discount_amount',
            'tax_amount' => 'lines.tax_amount',
            'charge_amount' => 'lines.charge_amount',
            'line_total' => 'lines.line_total',
        ];
        $sort = (string) ($params['sort'] ?? 'purchase_order_date');
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query
            ->orderBy($columns[$sort] ?? $columns['purchase_order_date'], $direction)
            ->orderBy('orders.id', 'desc')
            ->orderBy('lines.line_number');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'purchase_order_number' => (string) $row->purchase_order_number,
            'purchase_order_date' => (string) $row->purchase_order_date,
            'purchase_status' => (string) $row->purchase_status,
            'supplier' => trim((string) ($row->supplier_code ?? '').' '.(string) ($row->supplier_name ?? '')),
            'warehouse' => trim((string) ($row->warehouse_code ?? '').' '.(string) ($row->warehouse_name ?? '')),
            'line_number' => (int) $row->line_number,
            'item_code' => (string) ($row->item_code ?? ''),
            'item_name' => (string) ($row->item_name ?? ''),
            'variant' => trim((string) ($row->variant_code ?? '').' '.(string) ($row->variant_name ?? '')),
            'uom' => (string) ($row->uom_code ?? ''),
            'ordered_quantity' => $this->decimal($row->ordered_quantity),
            'received_quantity' => $this->decimal($row->received_quantity),
            'invoiced_quantity' => $this->decimal($row->invoiced_quantity),
            'returned_quantity' => $this->decimal($row->returned_quantity),
            'remaining_receivable_quantity' => $this->decimal($row->remaining_receivable_quantity),
            'remaining_invoiceable_quantity' => $this->decimal($row->remaining_invoiceable_quantity),
            'unit_price' => $this->decimal($row->unit_price),
            'line_subtotal' => $this->decimal($row->line_subtotal),
            'discount_amount' => $this->decimal($row->discount_amount),
            'tax_amount' => $this->decimal($row->tax_amount),
            'charge_amount' => $this->decimal($row->charge_amount),
            'line_total' => $this->decimal($row->line_total),
            'receipt_progress' => $this->progress($row->ordered_quantity, $row->received_quantity, $row->remaining_receivable_quantity),
            'invoice_progress' => $this->progress($row->ordered_quantity, $row->invoiced_quantity, $row->remaining_invoiceable_quantity),
        ];
    }

    private function progress(mixed $total, mixed $processed, mixed $remaining): string
    {
        if ($this->math->compare((string) $processed, '0') <= 0) {
            return 'not_started';
        }
        if ($this->math->compare((string) $remaining, '0') <= 0) {
            return 'completed';
        }
        if ($this->math->compare((string) $processed, (string) $total) >= 0) {
            return 'completed';
        }

        return 'partial';
    }

    private function organizationScope(Builder $query, string $column, mixed $organizationUnitId): void
    {
        if ($organizationUnitId === null || $organizationUnitId === '') {
            $query->whereNull($column);
            return;
        }

        $query->where($column, (int) $organizationUnitId);
    }

    private function searchTerm(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return '%'.str_replace(['%', '_'], ' ', $value).'%';
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? '0'));
    }
}
