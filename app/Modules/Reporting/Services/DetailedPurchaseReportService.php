<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\DTOs\ReportDefinition;

final class DetailedPurchaseReportService
{
    private const DEFAULT_PER_PAGE = 25;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly ReportDataFactory $documents,
    ) {}

    /** @param array<string, mixed> $params */
    public function run(array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($params['per_page'] ?? self::DEFAULT_PER_PAGE)));
        $paginator = $this->orderedOrders($params)->paginate($perPage, ['*'], 'page', $page);
        $orders = $paginator->getCollection();
        $actors = $this->actorsForOrders($orders, (int) $params['tenant_id']);
        $lines = $this->linesForOrders($orders->pluck('id')->all(), $params)
            ->groupBy('purchase_order_id');

        return [
            'data' => $orders
                ->map(fn (object $order): array => $this->orderRow($order, $lines->get($order->id, collect()), $actors))
                ->values()
                ->all(),
            'summary' => $this->summary($params),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'report' => $this->definition()->toArray(),
        ];
    }

    /** @param array<string, mixed> $params */
    public function exportData(array $params): ReportData
    {
        $matchingOrders = $this->matchingOrders($params);
        $lineQuery = DB::table('purchase_order_lines as lines')
            ->where('lines.tenant_id', (int) $params['tenant_id'])
            ->whereIn('lines.purchase_order_id', (clone $matchingOrders)->select('orders.id'));
        $this->organizationScope($lineQuery, 'lines.organization_unit_id', $params['organization_unit_id'] ?? null);
        $lineCount = $lineQuery->count();
        $limit = (int) config('reporting.export_row_limit', 5000);

        if ($lineCount > $limit) {
            throw ValidationException::withMessages([
                'filters' => ["The report contains {$lineCount} item lines. Narrow the filters to {$limit} lines or fewer before exporting."],
            ]);
        }

        $orders = $this->orderedOrders($params)->get();
        $actors = $this->actorsForOrders($orders, (int) $params['tenant_id']);
        $lines = $this->linesForOrders($orders->pluck('id')->all(), $params)->groupBy('purchase_order_id');
        $rows = $this->exportRows($orders, $lines, $actors, $this->summary($params));
        $filters = array_intersect_key($params, array_flip(['date_from', 'date_to', 'purchase_status', 'search']));
        if (isset($filters['purchase_status'])) {
            $filters['purchase_status'] = ucwords(str_replace('_', ' ', (string) $filters['purchase_status']));
        }
        if (! empty($params['supplier_id'])) {
            $filters['supplier'] = DB::table('suppliers')
                ->where('tenant_id', (int) $params['tenant_id'])
                ->where('id', (int) $params['supplier_id'])
                ->value('name') ?? 'Unknown supplier';
        }
        $document = $this->documents->make(
            $this->definition(),
            $rows,
            (int) $params['tenant_id'],
            $params['organization_unit_id'] ?? null,
            $filters,
        );

        return new ReportData(
            definition: $document->definition,
            rows: $document->rows,
            summary: $document->summary,
            branding: [...$document->branding, 'currency_code' => null],
            filters: $document->filters,
            generatedAt: $document->generatedAt,
            template: 'reports.purchase.order-detail',
        );
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'purchase/detailed',
            title: 'Purchase Order Detail Report',
            group: 'Purchase',
            model: PurchaseOrderLine::class,
            columns: [
                new ReportColumn('description', 'Item / PO'),
                new ReportColumn('unit_price', 'Cost price', format: 'money'),
                new ReportColumn('ordered_quantity', 'Order qty', format: 'decimal'),
                new ReportColumn('grn_quantity', 'GRN qty', format: 'decimal'),
                new ReportColumn('grn_cost', 'Total GRN cost', format: 'money'),
            ],
            dateColumn: 'purchase_order_date',
            defaultSort: 'purchase_order_date',
            defaultDirection: 'asc',
            description: 'Purchase orders grouped by supplier, with item quantities and posted GRN cost.',
            orientation: 'landscape',
        );
    }

    /** @param array<string, mixed> $params */
    private function matchingOrders(array $params): Builder
    {
        $query = DB::table('purchase_orders as orders')
            ->leftJoin('suppliers', function (JoinClause $join): void {
                $join->on('suppliers.id', '=', 'orders.supplier_id')
                    ->on('suppliers.tenant_id', '=', 'orders.tenant_id');
            })
            ->leftJoin('warehouses', function (JoinClause $join): void {
                $join->on('warehouses.id', '=', 'orders.warehouse_id')
                    ->on('warehouses.tenant_id', '=', 'orders.tenant_id');
            })
            ->leftJoin('currencies', 'currencies.id', '=', 'orders.currency_id')
            ->where('orders.tenant_id', (int) $params['tenant_id'])
            ->whereNull('orders.deleted_at');

        $this->organizationScope($query, 'orders.organization_unit_id', $params['organization_unit_id'] ?? null);

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
            $query->whereExists(function (Builder $lines) use ($params): void {
                $lines->selectRaw('1')
                    ->from('purchase_order_lines as matched_lines')
                    ->whereColumn('matched_lines.purchase_order_id', 'orders.id')
                    ->where('matched_lines.tenant_id', (int) $params['tenant_id'])
                    ->where('matched_lines.item_id', (int) $params['item_id']);
                $this->organizationScope($lines, 'matched_lines.organization_unit_id', $params['organization_unit_id'] ?? null);
            });
        }

        $search = $this->searchTerm($params['search'] ?? null);
        if ($search !== null) {
            $query->where(function (Builder $matches) use ($search, $params): void {
                $matches->where('orders.purchase_order_number', 'like', $search)
                    ->orWhere('suppliers.name', 'like', $search)
                    ->orWhereExists(function (Builder $lines) use ($search, $params): void {
                        $lines->selectRaw('1')
                            ->from('purchase_order_lines as matched_lines')
                            ->leftJoin('items as matched_items', function (JoinClause $join): void {
                                $join->on('matched_items.id', '=', 'matched_lines.item_id')
                                    ->on('matched_items.tenant_id', '=', 'matched_lines.tenant_id');
                            })
                            ->whereColumn('matched_lines.purchase_order_id', 'orders.id')
                            ->where('matched_lines.tenant_id', (int) $params['tenant_id'])
                            ->where(function (Builder $item) use ($search): void {
                                $item->where('matched_items.code', 'like', $search)
                                    ->orWhere('matched_items.name', 'like', $search)
                                    ->orWhere('matched_lines.description', 'like', $search);
                            });
                        $this->organizationScope($lines, 'matched_lines.organization_unit_id', $params['organization_unit_id'] ?? null);
                    });
            });
        }

        return $query;
    }

    /** @param array<string, mixed> $params */
    private function ordersWithCost(array $params): Builder
    {
        $query = $this->matchingOrders($params)
            ->leftJoinSub($this->orderReceiptTotals($params), 'order_grn', 'order_grn.purchase_order_id', '=', 'orders.id')
            ->select([
                'orders.id',
                'orders.purchase_order_number',
                'orders.purchase_order_date',
                'orders.status',
                'orders.created_by',
                'orders.submitted_by',
                'orders.approved_by',
                'orders.closed_by',
                'orders.cancelled_by',
                'orders.grand_total',
                'orders.supplier_id',
                'suppliers.name as supplier_name',
                'suppliers.code as supplier_code',
                'warehouses.name as warehouse_name',
                'currencies.code as currency_code',
            ])
            ->selectRaw('COALESCE(order_grn.grn_cost, 0) as grn_cost');

        return $query;
    }

    /** @param array<string, mixed> $params */
    private function orderedOrders(array $params): Builder
    {
        return $this->ordersWithCost($params)
            ->orderBy('suppliers.name')
            ->orderBy('orders.supplier_id')
            ->orderBy('currencies.code')
            ->orderBy('orders.purchase_order_date')
            ->orderBy('orders.purchase_order_number')
            ->orderBy('orders.id');
    }

    /** @param array<string, mixed> $params */
    private function receiptLineTotals(array $params): Builder
    {
        $query = DB::table('goods_receipt_note_lines as grn_lines')
            ->join('goods_receipt_notes as grns', 'grns.id', '=', 'grn_lines.goods_receipt_note_id')
            ->where('grn_lines.tenant_id', (int) $params['tenant_id'])
            ->where('grns.tenant_id', (int) $params['tenant_id'])
            ->where('grn_lines.status', GoodsReceiptNoteLineStatus::Posted->value)
            ->where('grns.status', GoodsReceiptNoteStatus::Posted->value)
            ->whereNull('grns.deleted_at')
            ->whereNotNull('grn_lines.purchase_order_line_id')
            ->select('grn_lines.purchase_order_line_id')
            ->selectRaw('SUM(grn_lines.accepted_quantity) as grn_quantity')
            ->selectRaw('SUM(grn_lines.accepted_quantity * grn_lines.unit_price) as grn_cost')
            ->groupBy('grn_lines.purchase_order_line_id');

        $this->organizationScope($query, 'grn_lines.organization_unit_id', $params['organization_unit_id'] ?? null);
        $this->organizationScope($query, 'grns.organization_unit_id', $params['organization_unit_id'] ?? null);

        return $query;
    }

    /** @param array<string, mixed> $params */
    private function orderReceiptTotals(array $params): Builder
    {
        $query = DB::table('purchase_order_lines as order_lines')
            ->leftJoinSub($this->receiptLineTotals($params), 'line_grn', 'line_grn.purchase_order_line_id', '=', 'order_lines.id')
            ->where('order_lines.tenant_id', (int) $params['tenant_id'])
            ->select('order_lines.purchase_order_id')
            ->selectRaw('SUM(COALESCE(line_grn.grn_cost, 0)) as grn_cost')
            ->groupBy('order_lines.purchase_order_id');

        $this->organizationScope($query, 'order_lines.organization_unit_id', $params['organization_unit_id'] ?? null);

        return $query;
    }

    /** @param array<int, int> $orderIds @param array<string, mixed> $params */
    private function linesForOrders(array $orderIds, array $params): Collection
    {
        if ($orderIds === []) {
            return collect();
        }

        $query = DB::table('purchase_order_lines as lines')
            ->leftJoin('items', function (JoinClause $join): void {
                $join->on('items.id', '=', 'lines.item_id')->on('items.tenant_id', '=', 'lines.tenant_id');
            })
            ->leftJoin('item_variants as variants', function (JoinClause $join): void {
                $join->on('variants.id', '=', 'lines.item_variant_id')->on('variants.tenant_id', '=', 'lines.tenant_id');
            })
            ->leftJoin('unit_of_measures as uoms', function (JoinClause $join): void {
                $join->on('uoms.id', '=', 'lines.uom_id')->on('uoms.tenant_id', '=', 'lines.tenant_id');
            })
            ->leftJoinSub($this->receiptLineTotals($params), 'line_grn', 'line_grn.purchase_order_line_id', '=', 'lines.id')
            ->where('lines.tenant_id', (int) $params['tenant_id'])
            ->whereIn('lines.purchase_order_id', $orderIds)
            ->select([
                'lines.id',
                'lines.purchase_order_id',
                'lines.line_number',
                'lines.description',
                'lines.ordered_quantity',
                'lines.unit_price',
                'items.code as item_code',
                'items.name as item_name',
                'variants.name as variant_name',
                'uoms.code as uom_code',
            ])
            ->selectRaw('COALESCE(line_grn.grn_quantity, 0) as grn_quantity')
            ->selectRaw('COALESCE(line_grn.grn_cost, 0) as grn_cost')
            ->orderBy('lines.purchase_order_id')
            ->orderBy('lines.line_number');

        $this->organizationScope($query, 'lines.organization_unit_id', $params['organization_unit_id'] ?? null);

        return $query->get();
    }

    /** @param Collection<int, object> $lines @param Collection<int, string> $actors */
    private function orderRow(object $order, Collection $lines, Collection $actors): array
    {
        $action = $this->statusAction($order);
        $actorName = $action['actor_id'] === null ? null : $actors->get($action['actor_id']);
        $items = $lines->map(fn (object $line): array => [
            'id' => (int) $line->id,
            'description' => (string) ($line->description ?: $line->item_name ?: 'Item'),
            'item_code' => (string) ($line->item_code ?? ''),
            'variant' => (string) ($line->variant_name ?? ''),
            'uom' => (string) ($line->uom_code ?? ''),
            'unit_price' => $this->decimal($line->unit_price),
            'ordered_quantity' => $this->decimal($line->ordered_quantity),
            'grn_quantity' => $this->decimal($line->grn_quantity),
            'grn_cost' => $this->decimal($line->grn_cost),
        ])->values()->all();

        $uoms = array_unique(array_column($items, 'uom'));
        $singleUom = count($uoms) === 1 && reset($uoms) !== '' ? (string) reset($uoms) : null;

        return [
            'id' => (int) $order->id,
            'purchase_order_number' => (string) $order->purchase_order_number,
            'purchase_order_date' => (string) $order->purchase_order_date,
            'status' => (string) $order->status,
            'status_action' => [
                'label' => $action['label'],
                'actor' => $actorName === null || trim($actorName) === '' ? null : ['name' => $actorName],
            ],
            'supplier' => [
                'id' => $order->supplier_id === null ? null : (int) $order->supplier_id,
                'name' => (string) ($order->supplier_name ?? 'Unassigned supplier'),
                'code' => (string) ($order->supplier_code ?? ''),
            ],
            'warehouse' => (string) ($order->warehouse_name ?? ''),
            'currency' => (string) ($order->currency_code ?? 'Unspecified currency'),
            'po_amount' => $this->decimal($order->grand_total),
            'grn_cost' => $this->decimal($order->grn_cost),
            'ordered_quantity' => $singleUom === null ? null : $this->math->sum(array_column($items, 'ordered_quantity')),
            'grn_quantity' => $singleUom === null ? null : $this->math->sum(array_column($items, 'grn_quantity')),
            'uom' => $singleUom,
            'items' => $items,
        ];
    }

    /** @param Collection<int, object> $orders @return Collection<int, string> */
    private function actorsForOrders(Collection $orders, int $tenantId): Collection
    {
        $actorIds = $orders
            ->map(fn (object $order): ?int => $this->statusAction($order)['actor_id'])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($actorIds === []) {
            return collect();
        }

        return DB::table('users')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $actorIds)
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->mapWithKeys(function (object $user): array {
                $name = trim((string) $user->first_name.' '.(string) $user->last_name);

                return [(int) $user->id => $name !== '' ? $name : (string) $user->email];
            });
    }

    /** @return array{label: string, actor_id: ?int} */
    private function statusAction(object $order): array
    {
        [$label, $actorId] = match (PurchaseOrderStatus::from((string) $order->status)) {
            PurchaseOrderStatus::Draft => ['Created by', $order->created_by],
            PurchaseOrderStatus::PendingApproval => ['Submitted by', $order->submitted_by],
            PurchaseOrderStatus::Approved => ['Approved by', $order->approved_by],
            PurchaseOrderStatus::Closed => ['Closed by', $order->closed_by],
            PurchaseOrderStatus::Cancelled => ['Cancelled by', $order->cancelled_by],
        };

        return ['label' => $label, 'actor_id' => $actorId === null ? null : (int) $actorId];
    }

    /** @param array<string, mixed> $params */
    private function summary(array $params): array
    {
        $groups = $this->matchingOrders($params)
            ->leftJoinSub($this->orderReceiptTotals($params), 'order_grn', 'order_grn.purchase_order_id', '=', 'orders.id')
            ->select([
                'orders.supplier_id',
                'suppliers.name as supplier_name',
                'currencies.code as currency_code',
            ])
            ->selectRaw('COUNT(orders.id) as order_count')
            ->selectRaw('SUM(orders.grand_total) as po_amount')
            ->selectRaw('SUM(COALESCE(order_grn.grn_cost, 0)) as grn_cost')
            ->groupBy('orders.supplier_id', 'suppliers.name', 'currencies.code')
            ->orderBy('suppliers.name')
            ->orderBy('currencies.code')
            ->get();

        $currencies = [];
        $suppliers = [];
        $orderCount = 0;
        $quantities = $this->quantityTotals($params);

        foreach ($groups as $group) {
            $currency = (string) ($group->currency_code ?? 'Unspecified currency');
            $poAmount = $this->decimal($group->po_amount);
            $grnCost = $this->decimal($group->grn_cost);
            $count = (int) $group->order_count;
            $orderCount += $count;
            $supplierKey = ($group->supplier_id ?? 'none').'|'.$currency;
            $supplierQuantities = $quantities['suppliers'][$supplierKey] ?? $this->emptyQuantities();
            $suppliers[] = [
                'supplier_id' => $group->supplier_id === null ? null : (int) $group->supplier_id,
                'supplier' => (string) ($group->supplier_name ?? 'Unassigned supplier'),
                'currency' => $currency,
                'order_count' => $count,
                'po_amount' => $poAmount,
                'grn_cost' => $grnCost,
                ...$supplierQuantities,
            ];
            $currencies[$currency] ??= ['currency' => $currency, 'po_amount' => '0', 'grn_cost' => '0'];
            $currencies[$currency]['po_amount'] = $this->math->add($currencies[$currency]['po_amount'], $poAmount);
            $currencies[$currency]['grn_cost'] = $this->math->add($currencies[$currency]['grn_cost'], $grnCost);
        }

        foreach ($currencies as $currency => &$total) {
            $total = [...$total, ...($quantities['currencies'][$currency] ?? $this->emptyQuantities())];
        }
        unset($total);

        return [
            'total_orders' => $orderCount,
            'currencies' => array_values($currencies),
            'suppliers' => $suppliers,
        ];
    }

    /** @param array<string, mixed> $params */
    private function quantityTotals(array $params): array
    {
        $query = DB::table('purchase_order_lines as lines')
            ->join('purchase_orders as orders', 'orders.id', '=', 'lines.purchase_order_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'orders.currency_id')
            ->leftJoin('unit_of_measures as uoms', function (JoinClause $join): void {
                $join->on('uoms.id', '=', 'lines.uom_id')->on('uoms.tenant_id', '=', 'lines.tenant_id');
            })
            ->leftJoinSub($this->receiptLineTotals($params), 'line_grn', 'line_grn.purchase_order_line_id', '=', 'lines.id')
            ->where('lines.tenant_id', (int) $params['tenant_id'])
            ->whereIn('lines.purchase_order_id', (clone $this->matchingOrders($params))->select('orders.id'))
            ->select(['orders.supplier_id', 'currencies.code as currency_code', 'uoms.code as uom_code'])
            ->selectRaw('SUM(lines.ordered_quantity) as ordered_quantity')
            ->selectRaw('SUM(COALESCE(line_grn.grn_quantity, 0)) as grn_quantity')
            ->groupBy('orders.supplier_id', 'currencies.code', 'uoms.code');

        $this->organizationScope($query, 'lines.organization_unit_id', $params['organization_unit_id'] ?? null);
        $rows = $query->get();

        $supplierUnits = [];
        $currencyUnits = [];

        foreach ($rows as $row) {
            $currency = (string) ($row->currency_code ?? 'Unspecified currency');
            $uom = (string) ($row->uom_code ?? '');
            $supplierKey = ($row->supplier_id ?? 'none').'|'.$currency;
            $ordered = $this->decimal($row->ordered_quantity);
            $received = $this->decimal($row->grn_quantity);
            $supplierUnits[$supplierKey][$uom] = ['uom' => $uom, 'ordered_quantity' => $ordered, 'grn_quantity' => $received];
            $currencyUnits[$currency][$uom] ??= ['uom' => $uom, 'ordered_quantity' => '0', 'grn_quantity' => '0'];
            $currencyUnits[$currency][$uom]['ordered_quantity'] = $this->math->add($currencyUnits[$currency][$uom]['ordered_quantity'], $ordered);
            $currencyUnits[$currency][$uom]['grn_quantity'] = $this->math->add($currencyUnits[$currency][$uom]['grn_quantity'], $received);
        }

        return [
            'suppliers' => array_map(fn (array $units): array => $this->singleUomQuantities($units), $supplierUnits),
            'currencies' => array_map(fn (array $units): array => $this->singleUomQuantities($units), $currencyUnits),
        ];
    }

    /** @param array<string, array<string, string>> $units */
    private function singleUomQuantities(array $units): array
    {
        if (count($units) !== 1 || array_key_first($units) === '') {
            return $this->emptyQuantities();
        }

        return reset($units);
    }

    private function emptyQuantities(): array
    {
        return ['uom' => null, 'ordered_quantity' => null, 'grn_quantity' => null];
    }

    /** @param Collection<int, object> $orders @param Collection<int, Collection<int, object>> $lines @param Collection<int, string> $actors @param array<string, mixed> $summary */
    private function exportRows(Collection $orders, Collection $lines, Collection $actors, array $summary): array
    {
        $rows = [];
        $supplierKey = null;
        $currency = null;
        $supplier = '';
        $supplierTotals = collect($summary['suppliers'])->keyBy(fn (array $total): string => ($total['supplier_id'] ?? 'none').'|'.$total['currency']);

        foreach ($orders as $order) {
            $currentSupplierKey = (string) ($order->supplier_id ?? 'none');
            $currentCurrency = (string) ($order->currency_code ?? 'Unspecified currency');
            if ($supplierKey !== null && ($currentSupplierKey !== $supplierKey || $currentCurrency !== $currency)) {
                $rows[] = $this->totalRow('supplier_total', "{$supplier} total ({$currency})", $supplierTotals[$supplierKey.'|'.$currency]);
            }
            if ($currentSupplierKey !== $supplierKey) {
                $supplier = (string) ($order->supplier_name ?? 'Unassigned supplier');
                $rows[] = ['row_type' => 'supplier', 'description' => $supplier];
            }
            $supplierKey = $currentSupplierKey;
            $currency = $currentCurrency;
            $po = $this->orderRow($order, $lines->get($order->id, collect()), $actors);
            $status = ucwords(str_replace('_', ' ', $po['status']));
            $poAmount = $this->displayDecimal($po['po_amount']);
            $action = $po['status_action'];
            $actionText = $action['label'].': '.($action['actor']['name'] ?? 'Not recorded');
            $rows[] = [
                'row_type' => 'order',
                'description' => "{$po['purchase_order_number']} | {$po['purchase_order_date']} | {$status} | {$actionText} | PO amount {$po['currency']} {$poAmount}",
            ];
            foreach ($po['items'] as $item) {
                $rows[] = [
                    'row_type' => 'item',
                    'description' => trim("{$item['description']} {$item['variant']} ({$item['uom']})"),
                    'unit_price' => $item['unit_price'],
                    'ordered_quantity' => $item['ordered_quantity'],
                    'grn_quantity' => $item['grn_quantity'],
                    'grn_cost' => $item['grn_cost'],
                ];
            }
            $rows[] = [
                'row_type' => 'order_total',
                'description' => "{$po['purchase_order_number']} total".($po['items'] === [] ? '' : ($po['uom'] === null ? ' (mixed units)' : " ({$po['uom']})")),
                'ordered_quantity' => $po['ordered_quantity'],
                'grn_quantity' => $po['grn_quantity'],
                'grn_cost' => $po['grn_cost'],
            ];
        }

        if ($supplierKey !== null) {
            $rows[] = $this->totalRow('supplier_total', "{$supplier} total ({$currency})", $supplierTotals[$supplierKey.'|'.$currency]);
        }
        foreach ($summary['currencies'] as $total) {
            $rows[] = $this->totalRow('report_total', "Report total ({$total['currency']})", $total);
        }

        return $rows;
    }

    /** @param array<string, mixed> $total */
    private function totalRow(string $type, string $description, array $total): array
    {
        return [
            'row_type' => $type,
            'description' => $description.($total['uom'] === null ? '' : " ({$total['uom']})"),
            'ordered_quantity' => $total['ordered_quantity'],
            'grn_quantity' => $total['grn_quantity'],
            'grn_cost' => $total['grn_cost'],
        ];
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

        return $value === '' ? null : '%'.str_replace(['%', '_'], ' ', $value).'%';
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? '0'));
    }

    private function displayDecimal(string $value): string
    {
        [$whole, $fraction] = explode('.', $value, 2);

        return $whole.'.'.str_pad(rtrim($fraction, '0'), 2, '0');
    }
}
