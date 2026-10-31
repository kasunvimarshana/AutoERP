<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\DTOs\ReportDefinition;

final class StockBalanceReportService
{
    public const KEY = 'inventory.stock-balance';

    public function __construct(private readonly ReportDataFactory $documents) {}

    /** @param array<string, mixed> $params */
    public function run(array $params): array
    {
        $page = $this->query($params)->paginate((int) ($params['per_page'] ?? 25));

        return [
            'data' => $page->getCollection()->map(fn (object $row): array => $this->row($row))->all(),
            'meta' => [
                'current_page' => $page->currentPage(), 'from' => $page->firstItem(),
                'last_page' => $page->lastPage(), 'per_page' => $page->perPage(),
                'to' => $page->lastItem(), 'total' => $page->total(),
            ],
            'report' => $this->definition()->toArray(),
            'generated_at' => now()->toISOString(),
        ];
    }

    /** @param array<string, mixed> $params */
    public function details(array $params): array
    {
        $query = DB::table('inventory_stock_balances as balances')
            ->leftJoin('inventory_batches as batches', 'batches.id', '=', 'balances.batch_id')
            ->where('balances.tenant_id', (int) $params['tenant_id'])
            ->where('balances.item_id', (int) $params['item_id'])
            ->where('balances.warehouse_id', (int) $params['warehouse_id']);
        $this->scopeOrganization($query, $params['organization_unit_id'] ?? null);
        foreach (['warehouse_location_id' => 'location_id', 'base_uom_id' => 'base_uom_id', 'item_variant_id' => 'item_variant_id'] as $column => $key) {
            isset($params[$key]) ? $query->where('balances.'.$column, (int) $params[$key]) : $query->whereNull('balances.'.$column);
        }

        return $query->orderBy('batches.batch_number')->get([
            'batches.batch_number', 'balances.quantity_on_hand', 'balances.quantity_available',
            'balances.quantity_reserved', 'balances.quantity_allocated', 'balances.quantity_returned',
            'balances.quantity_in_transit', 'balances.quantity_damaged', 'balances.quantity_quarantine',
            'balances.quantity_expired', 'balances.quantity_scrapped',
        ])->map(static fn (object $balance): array => (array) $balance)->all();
    }

    /** @param array<string, mixed> $params */
    public function exportData(array $params): ReportData
    {
        $query = $this->query($params);
        $limit = (int) config('reporting.export_row_limit', 5000);
        if ((clone $query)->getCountForPagination() > $limit) {
            throw ValidationException::withMessages(['filters' => "Narrow the report to {$limit} item/location rows or fewer before exporting."]);
        }

        return $this->documents->make(
            $this->definition(),
            $query->get()->map(fn (object $row): array => $this->row($row))->all(),
            (int) $params['tenant_id'],
            $params['organization_unit_id'] ?? null,
            array_intersect_key($params, array_flip(['search', 'include_zero'])),
        );
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: self::KEY,
            title: 'Stock Balance',
            group: 'Inventory',
            model: InventoryStockBalance::class,
            columns: [
                new ReportColumn('item_name', 'Item'), new ReportColumn('item_code', 'Code'),
                new ReportColumn('variant_name', 'Variant'), new ReportColumn('variant_code', 'Variant code'),
                new ReportColumn('warehouse', 'Warehouse'), new ReportColumn('location', 'Location'),
                new ReportColumn('uom', 'Unit'),
                new ReportColumn('quantity_on_hand', 'Current stock', format: 'decimal'),
                new ReportColumn('quantity_available', 'Available to sell', format: 'decimal'),
                new ReportColumn('quantity_damaged', 'Damaged', format: 'decimal'),
                new ReportColumn('quantity_expired', 'Expired', format: 'decimal'),
            ],
            description: 'Current item balances by warehouse and location, with sellable, damaged and expired stock.',
            orientation: 'landscape',
        );
    }

    /** @param array<string, mixed> $params */
    private function query(array $params): Builder
    {
        $query = DB::table('inventory_stock_balances as balances')
            ->join('items', 'items.id', '=', 'balances.item_id')
            ->leftJoin('item_variants as variants', 'variants.id', '=', 'balances.item_variant_id')
            ->join('warehouses', 'warehouses.id', '=', 'balances.warehouse_id')
            ->leftJoin('warehouse_locations as locations', 'locations.id', '=', 'balances.warehouse_location_id')
            ->leftJoin('unit_of_measures as uoms', 'uoms.id', '=', 'balances.base_uom_id')
            ->where('balances.tenant_id', (int) $params['tenant_id'])
            ->where('items.tenant_id', (int) $params['tenant_id'])
            ->where('warehouses.tenant_id', (int) $params['tenant_id']);
        $this->scopeOrganization($query, $params['organization_unit_id'] ?? null);
        if (! empty($params['warehouse_id'])) {
            $query->where('balances.warehouse_id', (int) $params['warehouse_id']);
        }
        if (! empty($params['location_id'])) {
            $query->where('balances.warehouse_location_id', (int) $params['location_id']);
        }
        if (! empty($params['search'])) {
            $search = '%'.trim((string) $params['search']).'%';
            $query->where(static fn (Builder $items): Builder => $items->where('items.name', 'like', $search)
                ->orWhere('items.code', 'like', $search)
                ->orWhere('items.sku', 'like', $search));
        }

        $query->select([
            'balances.item_id', 'balances.item_variant_id', 'balances.base_uom_id',
            'balances.warehouse_id', 'balances.warehouse_location_id as location_id',
            'items.name as item_name', 'items.code as item_code', 'items.reorder_level',
            'variants.name as variant_name', 'variants.code as variant_code',
            'warehouses.name as warehouse', 'locations.name as location', 'uoms.code as uom',
        ])->selectRaw('SUM(balances.quantity_on_hand) as quantity_on_hand')
            ->selectRaw('SUM(balances.quantity_available) as quantity_available')
            ->selectRaw('SUM(balances.quantity_damaged) as quantity_damaged')
            ->selectRaw('SUM(balances.quantity_expired) as quantity_expired')
            ->groupBy(
                'balances.item_id', 'balances.item_variant_id', 'balances.base_uom_id',
                'balances.warehouse_id', 'balances.warehouse_location_id',
                'items.name', 'items.code', 'items.reorder_level', 'variants.name', 'variants.code',
                'warehouses.name', 'locations.name', 'uoms.code',
            );
        if (! ($params['include_zero'] ?? true)) {
            $query->havingRaw('SUM(balances.quantity_on_hand) <> 0');
        }

        return $query->orderBy('warehouses.name')->orderBy('locations.name')->orderBy('items.name')->orderBy('balances.item_id');
    }

    private function scopeOrganization(Builder $query, ?int $organizationUnitId): void
    {
        $organizationUnitId === null
            ? $query->whereNull('balances.organization_unit_id')
            : $query->where('balances.organization_unit_id', $organizationUnitId);
    }

    /** @return array<string, mixed> */
    private function row(object $row): array
    {
        $available = (float) $row->quantity_available;
        $threshold = $row->reorder_level;

        return [
            ...(array) $row,
            'stock_level' => $available <= 0 ? 'out_of_stock'
                : ($threshold !== null && $available <= (float) $threshold ? 'low_stock' : 'in_stock'),
        ];
    }
}
