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
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\DTOs\ReportDefinition;

final class GoodsReceiptSummaryReportService
{
    private const REPORT_KEY = 'purchase/goods-receipt-summary';

    private const DEFAULT_PER_PAGE = 25;

    private const MAX_PER_PAGE = 100;

    private const ZERO = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly ReportDataFactory $documents,
    ) {}

    /** @param array<string, mixed> $params */
    public function run(array $params): array
    {
        $currency = $this->baseCurrency((int) $params['tenant_id']);
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($params['per_page'] ?? self::DEFAULT_PER_PAGE)));
        $paginator = $this->orderedReceipts($params)->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (object $receipt): array => $this->receiptRow($receipt, $currency))
                ->values()
                ->all(),
            'summary' => $this->summary($params, $currency),
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
        $query = $this->orderedReceipts($params);
        $count = (clone $query)->count();
        $limit = (int) config('reporting.export_row_limit', 5000);
        if ($count > $limit) {
            throw ValidationException::withMessages([
                'filters' => ["The report contains {$count} GRNs. Narrow the filters to {$limit} GRNs or fewer before exporting."],
            ]);
        }

        $currency = $this->baseCurrency((int) $params['tenant_id']);
        $receipts = $query->get()->map(fn (object $receipt): array => $this->receiptRow($receipt, $currency));
        $summary = $this->summary($params, $currency);
        $rows = $this->exportRows($receipts, $summary);
        $filters = array_intersect_key($params, array_flip(['date_from', 'date_to', 'search']));
        if (! empty($params['supplier_id'])) {
            $filters['supplier'] = DB::table('suppliers')
                ->where('tenant_id', (int) $params['tenant_id'])
                ->where('id', (int) $params['supplier_id'])
                ->value('name') ?? 'Unknown supplier';
        }
        if (! empty($params['warehouse_id'])) {
            $filters['warehouse'] = DB::table('warehouses')
                ->where('tenant_id', (int) $params['tenant_id'])
                ->where('id', (int) $params['warehouse_id'])
                ->value('name') ?? 'Unknown warehouse';
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
            template: 'reports.purchase.goods-receipt-summary',
        );
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: self::REPORT_KEY,
            title: 'Goods Receipt Note Summary Report',
            group: 'Purchase',
            model: GoodsReceiptNote::class,
            columns: [
                new ReportColumn('grn_number', 'GRN'),
                new ReportColumn('received_date', 'GRN date', format: 'date'),
                new ReportColumn('supplier', 'Supplier'),
                new ReportColumn('purchase_order', 'PO'),
                new ReportColumn('note', 'Note'),
                new ReportColumn('accepted_quantity', 'Accepted qty'),
                new ReportColumn('total_cost', 'Total cost', format: 'money'),
            ],
            dateColumn: 'received_date',
            defaultSort: 'received_date',
            defaultDirection: 'asc',
            description: 'Posted GRNs by location, with accepted quantity and cost before adjustments.',
            orientation: 'landscape',
        );
    }

    /** @param array<string, mixed> $params */
    private function matchingReceipts(array $params): Builder
    {
        $tenantId = (int) $params['tenant_id'];
        $lines = DB::table('goods_receipt_note_lines as receipt_lines')
            ->where('receipt_lines.tenant_id', $tenantId)
            ->where('receipt_lines.status', GoodsReceiptNoteLineStatus::Posted->value)
            ->select('receipt_lines.goods_receipt_note_id')
            ->selectRaw('SUM(receipt_lines.accepted_quantity) as accepted_quantity')
            ->selectRaw('SUM(receipt_lines.accepted_quantity * receipt_lines.unit_price) as total_cost')
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('COUNT(receipt_lines.uom_id) as uom_count')
            ->selectRaw('MIN(receipt_lines.uom_id) as min_uom_id')
            ->selectRaw('MAX(receipt_lines.uom_id) as max_uom_id')
            ->groupBy('receipt_lines.goods_receipt_note_id');
        $query = DB::table('goods_receipt_notes as receipts')
            ->leftJoin('organization_units as locations', function (JoinClause $join): void {
                $join->on('locations.id', '=', 'receipts.organization_unit_id')
                    ->on('locations.tenant_id', '=', 'receipts.tenant_id');
            })
            ->leftJoin('suppliers', function (JoinClause $join): void {
                $join->on('suppliers.id', '=', 'receipts.supplier_id')
                    ->on('suppliers.tenant_id', '=', 'receipts.tenant_id');
            })
            ->leftJoin('purchase_orders as orders', function (JoinClause $join): void {
                $join->on('orders.id', '=', 'receipts.purchase_order_id')
                    ->on('orders.tenant_id', '=', 'receipts.tenant_id');
            })
            ->leftJoin('currencies', 'currencies.id', '=', 'orders.currency_id')
            ->leftJoinSub($lines, 'line_totals', 'line_totals.goods_receipt_note_id', '=', 'receipts.id')
            ->leftJoin('unit_of_measures as units', function (JoinClause $join): void {
                $join->on('units.id', '=', 'line_totals.min_uom_id')
                    ->on('units.tenant_id', '=', 'receipts.tenant_id');
            })
            ->where('receipts.tenant_id', $tenantId)
            ->where('receipts.status', GoodsReceiptNoteStatus::Posted->value)
            ->whereNull('receipts.deleted_at');

        if (isset($params['organization_unit_id'])) {
            $query->where('receipts.organization_unit_id', (int) $params['organization_unit_id']);
        }
        if (! empty($params['date_from'])) {
            $query->whereDate('receipts.received_date', '>=', (string) $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('receipts.received_date', '<=', (string) $params['date_to']);
        }
        if (! empty($params['supplier_id'])) {
            $query->where('receipts.supplier_id', (int) $params['supplier_id']);
        }
        if (! empty($params['warehouse_id'])) {
            $query->where('receipts.warehouse_id', (int) $params['warehouse_id']);
        }
        $search = trim((string) ($params['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $matches) use ($search): void {
                $pattern = '%'.addcslashes($search, '%_\\').'%';
                $matches->where('receipts.grn_number', 'like', $pattern)
                    ->orWhere('orders.purchase_order_number', 'like', $pattern)
                    ->orWhere('suppliers.name', 'like', $pattern)
                    ->orWhere('receipts.notes', 'like', $pattern);
            });
        }

        return $query;
    }

    /** @param array<string, mixed> $params */
    private function orderedReceipts(array $params): Builder
    {
        return $this->matchingReceipts($params)
            ->select([
                'receipts.id',
                'receipts.grn_number',
                'receipts.received_date',
                'receipts.notes',
                'receipts.organization_unit_id',
                'locations.name as location_name',
                'receipts.purchase_order_id',
                'orders.purchase_order_number',
                'suppliers.name as supplier_name',
                'currencies.code as currency_code',
                'line_totals.accepted_quantity',
                'line_totals.total_cost',
                'line_totals.line_count',
                'line_totals.uom_count',
                'line_totals.min_uom_id',
                'line_totals.max_uom_id',
                'units.code as uom_code',
            ])
            ->orderBy('locations.name')
            ->orderBy('receipts.organization_unit_id')
            ->orderByRaw('COALESCE(currencies.code, ?)', [$this->baseCurrency((int) $params['tenant_id'])])
            ->orderBy('receipts.received_date')
            ->orderBy('receipts.grn_number')
            ->orderBy('receipts.id');
    }

    private function baseCurrency(int $tenantId): string
    {
        return (string) (DB::table('tenants')
            ->leftJoin('currencies', 'currencies.id', '=', 'tenants.base_currency_id')
            ->where('tenants.id', $tenantId)
            ->value('currencies.code') ?? 'Currency unavailable');
    }

    private function receiptRow(object $receipt, string $baseCurrency): array
    {
        $uom = $this->singleUom(
            (int) ($receipt->line_count ?? 0),
            (int) ($receipt->uom_count ?? 0),
            $receipt->min_uom_id,
            $receipt->max_uom_id,
            $receipt->uom_code,
        );

        return [
            'id' => (int) $receipt->id,
            'grn_number' => (string) $receipt->grn_number,
            'received_date' => (string) $receipt->received_date,
            'supplier' => (string) ($receipt->supplier_name ?? 'Supplier unavailable'),
            'purchase_order' => $receipt->purchase_order_id === null ? null : [
                'id' => (int) $receipt->purchase_order_id,
                'number' => (string) ($receipt->purchase_order_number ?? 'PO unavailable'),
            ],
            'note' => (string) ($receipt->notes ?? ''),
            'location_id' => $receipt->organization_unit_id === null ? null : (int) $receipt->organization_unit_id,
            'location' => (string) ($receipt->location_name ?? 'Unassigned location'),
            'currency' => (string) ($receipt->currency_code ?? $baseCurrency),
            'accepted_quantity' => $uom === null ? null : $this->decimal($receipt->accepted_quantity),
            'uom' => $uom,
            'total_cost' => $this->decimal($receipt->total_cost),
        ];
    }

    /** @param array<string, mixed> $params */
    private function summary(array $params, string $baseCurrency): array
    {
        $groups = $this->matchingReceipts($params)
            ->select([
                'receipts.organization_unit_id',
                'locations.name as location_name',
                'currencies.code as currency_code',
            ])
            ->selectRaw('COUNT(receipts.id) as receipt_count')
            ->selectRaw('SUM(COALESCE(line_totals.accepted_quantity, 0)) as accepted_quantity')
            ->selectRaw('SUM(COALESCE(line_totals.total_cost, 0)) as total_cost')
            ->selectRaw('SUM(COALESCE(line_totals.line_count, 0)) as line_count')
            ->selectRaw('SUM(COALESCE(line_totals.uom_count, 0)) as uom_count')
            ->selectRaw('MIN(line_totals.min_uom_id) as min_uom_id')
            ->selectRaw('MAX(line_totals.max_uom_id) as max_uom_id')
            ->selectRaw('MIN(units.code) as uom_code')
            ->groupBy('receipts.organization_unit_id', 'locations.name', 'currencies.code')
            ->get();

        $locations = [];
        foreach ($groups as $group) {
            $currency = (string) ($group->currency_code ?? $baseCurrency);
            $key = ($group->organization_unit_id ?? 'none').'|'.$currency;
            $locations[$key] ??= [
                'location_id' => $group->organization_unit_id === null ? null : (int) $group->organization_unit_id,
                'location' => (string) ($group->location_name ?? 'Unassigned location'),
                'currency' => $currency,
                'receipt_count' => 0,
                'accepted_quantity_raw' => self::ZERO,
                'total_cost' => self::ZERO,
                'line_count' => 0,
                'uom_count' => 0,
                'min_uom_id' => null,
                'max_uom_id' => null,
                'uom_code' => null,
            ];
            $total = &$locations[$key];
            $total['receipt_count'] += (int) $group->receipt_count;
            $total['accepted_quantity_raw'] = $this->math->add($total['accepted_quantity_raw'], $this->decimal($group->accepted_quantity));
            $total['total_cost'] = $this->math->add($total['total_cost'], $this->decimal($group->total_cost));
            $total['line_count'] += (int) $group->line_count;
            $total['uom_count'] += (int) $group->uom_count;
            $total['min_uom_id'] = $total['min_uom_id'] === null ? $group->min_uom_id : min($total['min_uom_id'], $group->min_uom_id ?? $total['min_uom_id']);
            $total['max_uom_id'] = $total['max_uom_id'] === null ? $group->max_uom_id : max($total['max_uom_id'], $group->max_uom_id ?? $total['max_uom_id']);
            $total['uom_code'] ??= $group->uom_code;
            unset($total);
        }

        $currencies = [];
        $receiptCount = 0;
        foreach ($locations as &$location) {
            $location['uom'] = $this->singleUom($location['line_count'], $location['uom_count'], $location['min_uom_id'], $location['max_uom_id'], $location['uom_code']);
            $location['accepted_quantity'] = $location['uom'] === null ? null : $location['accepted_quantity_raw'];
            $receiptCount += $location['receipt_count'];
            $currency = $location['currency'];
            $currencies[$currency] ??= [
                'currency' => $currency,
                'receipt_count' => 0,
                'accepted_quantity_raw' => self::ZERO,
                'total_cost' => self::ZERO,
                'line_count' => 0,
                'uom_count' => 0,
                'min_uom_id' => null,
                'max_uom_id' => null,
                'uom_code' => null,
            ];
            $total = &$currencies[$currency];
            $total['receipt_count'] += $location['receipt_count'];
            $total['accepted_quantity_raw'] = $this->math->add($total['accepted_quantity_raw'], $location['accepted_quantity_raw']);
            $total['total_cost'] = $this->math->add($total['total_cost'], $location['total_cost']);
            $total['line_count'] += $location['line_count'];
            $total['uom_count'] += $location['uom_count'];
            $total['min_uom_id'] = $total['min_uom_id'] === null ? $location['min_uom_id'] : min($total['min_uom_id'], $location['min_uom_id'] ?? $total['min_uom_id']);
            $total['max_uom_id'] = $total['max_uom_id'] === null ? $location['max_uom_id'] : max($total['max_uom_id'], $location['max_uom_id'] ?? $total['max_uom_id']);
            $total['uom_code'] ??= $location['uom_code'];
            unset($total);
            unset($location['accepted_quantity_raw'], $location['line_count'], $location['uom_count'], $location['min_uom_id'], $location['max_uom_id'], $location['uom_code']);
        }
        unset($location);

        foreach ($currencies as &$total) {
            $total['uom'] = $this->singleUom($total['line_count'], $total['uom_count'], $total['min_uom_id'], $total['max_uom_id'], $total['uom_code']);
            $total['accepted_quantity'] = $total['uom'] === null ? null : $total['accepted_quantity_raw'];
            unset($total['accepted_quantity_raw'], $total['line_count'], $total['uom_count'], $total['min_uom_id'], $total['max_uom_id'], $total['uom_code']);
        }
        unset($total);

        return [
            'total_receipts' => $receiptCount,
            'locations' => array_values($locations),
            'currencies' => array_values($currencies),
        ];
    }

    private function singleUom(int $lineCount, int $uomCount, mixed $minimum, mixed $maximum, mixed $code): ?string
    {
        if ($lineCount === 0 || $lineCount !== $uomCount || $minimum === null || (string) $minimum !== (string) $maximum) {
            return null;
        }

        $value = trim((string) $code);

        return $value === '' ? null : $value;
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? self::ZERO));
    }

    /** @param Collection<int, array<string, mixed>> $receipts @param array<string, mixed> $summary */
    private function exportRows(Collection $receipts, array $summary): array
    {
        $rows = [];
        $currentKey = null;
        $locationTotals = collect($summary['locations'])->keyBy(fn (array $total): string => ($total['location_id'] ?? 'none').'|'.$total['currency']);

        foreach ($receipts as $receipt) {
            $key = ($receipt['location_id'] ?? 'none').'|'.$receipt['currency'];
            if ($key !== $currentKey) {
                if ($currentKey !== null) {
                    $rows[] = $this->totalRow('location_total', $locationTotals[$currentKey]['location'].' total ('.$locationTotals[$currentKey]['currency'].')', $locationTotals[$currentKey]);
                }
                $rows[] = ['row_type' => 'location', 'grn_number' => $receipt['location'].' ('.$receipt['currency'].')'];
                $currentKey = $key;
            }
            $rows[] = [
                'row_type' => 'receipt',
                'grn_number' => $receipt['grn_number'],
                'received_date' => $receipt['received_date'],
                'supplier' => $receipt['supplier'],
                'purchase_order' => $receipt['purchase_order']['number'] ?? '',
                'note' => $receipt['note'],
                'accepted_quantity' => $this->quantityText($receipt['accepted_quantity'], $receipt['uom']),
                'total_cost' => $receipt['total_cost'],
            ];
        }
        if ($currentKey !== null) {
            $rows[] = $this->totalRow('location_total', $locationTotals[$currentKey]['location'].' total ('.$locationTotals[$currentKey]['currency'].')', $locationTotals[$currentKey]);
        }
        foreach ($summary['currencies'] as $total) {
            $rows[] = $this->totalRow('report_total', 'Grand total ('.$total['currency'].')', $total);
        }

        return $rows;
    }

    /** @param array<string, mixed> $total */
    private function totalRow(string $type, string $label, array $total): array
    {
        return [
            'row_type' => $type,
            'grn_number' => $label,
            'accepted_quantity' => $this->quantityText($total['accepted_quantity'], $total['uom']),
            'total_cost' => $total['total_cost'],
        ];
    }

    private function quantityText(?string $quantity, ?string $uom): string
    {
        return $quantity === null ? 'Mixed units' : "{$quantity} {$uom}";
    }
}
