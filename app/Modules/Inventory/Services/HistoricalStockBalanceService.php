<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Enums\InventoryStockLevel;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Item\Models\Item;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class HistoricalStockBalanceService
{
    private const ON_HAND_STATES = [
        InventoryStockState::Available,
        InventoryStockState::Reserved,
        InventoryStockState::Allocated,
        InventoryStockState::Returned,
        InventoryStockState::Damaged,
        InventoryStockState::Quarantine,
        InventoryStockState::Expired,
        InventoryStockState::Scrapped,
    ];

    /** @return array{0: LengthAwarePaginator, 1: array<string, int>} */
    public function page(InventoryLookupRequest $request, \DateTimeInterface $cutoff): array
    {
        $query = $this->query($request, $cutoff);
        $summary = $this->summary(clone $query);
        $this->applyStockLevel($query, $request);

        $paginator = $query
            ->orderBy('quantity_available')
            ->orderBy('history.item_id')
            ->orderBy('history.item_variant_id')
            ->orderBy('history.warehouse_id')
            ->orderBy('history.warehouse_location_id')
            ->orderBy('history.batch_id')
            ->paginate($request->perPage());
        $paginator->getCollection()->transform(fn (object $row): InventoryStockBalance => $this->balance($row));

        return [$paginator, $summary];
    }

    public function rows(InventoryLookupRequest $request, \DateTimeInterface $cutoff): iterable
    {
        $query = $this->query($request, $cutoff);
        $this->applyStockLevel($query, $request);

        return $query->orderBy('quantity_available')
            ->orderBy('history.item_id')
            ->orderBy('history.item_variant_id')
            ->orderBy('history.warehouse_id')
            ->orderBy('history.warehouse_location_id')
            ->orderBy('history.batch_id')
            ->lazy(500)
            ->map(fn (object $row): InventoryStockBalance => $this->balance($row));
    }

    private function query(InventoryLookupRequest $request, \DateTimeInterface $cutoff): Builder
    {
        $changes = DB::table('inventory_stock_state_changes as changes')
            ->where('changes.tenant_id', $request->tenantId())
            ->where('changes.occurred_at', '<=', $cutoff)
            ->whereNotNull('changes.occurred_at')
            ->select([
                'changes.tenant_id',
                'changes.organization_unit_id',
                'changes.item_id',
                'changes.item_variant_id',
                'changes.warehouse_id',
                'changes.warehouse_location_id',
                'changes.batch_id',
            ]);

        if ($request->organizationUnitId() === null) {
            $changes->whereNull('changes.organization_unit_id');
        } else {
            $changes->where('changes.organization_unit_id', $request->organizationUnitId());
        }

        foreach ([
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
        ] as $filter) {
            if ($request->filled($filter)) {
                $changes->where('changes.'.$filter, $request->input($filter));
            }
        }

        foreach (InventoryStockState::cases() as $state) {
            $alias = $state->value.'_quantity';
            $changes->selectRaw(
                "SUM(CASE WHEN changes.to_state = ? THEN changes.quantity ELSE 0 END) - SUM(CASE WHEN changes.from_state = ? THEN changes.quantity ELSE 0 END) AS {$alias}",
                [$state->value, $state->value],
            );
        }
        $changes->selectRaw(
            'SUM(CASE WHEN changes.to_state = ? AND changes.from_state IN (?, ?) THEN changes.quantity ELSE 0 END) AS reversed_to_available_quantity',
            [InventoryStockState::Reversed->value, InventoryStockState::Issued->value, InventoryStockState::InTransit->value],
        );

        $changes->groupBy([
            'changes.tenant_id',
            'changes.organization_unit_id',
            'changes.item_id',
            'changes.item_variant_id',
            'changes.warehouse_id',
            'changes.warehouse_location_id',
            'changes.batch_id',
        ]);

        $query = DB::query()
            ->fromSub($changes, 'history')
            ->join('items', static function ($join): void {
                $join->on('items.id', '=', 'history.item_id')
                    ->on('items.tenant_id', '=', 'history.tenant_id');
            })
            ->join('warehouses', static function ($join): void {
                $join->on('warehouses.id', '=', 'history.warehouse_id')
                    ->on('warehouses.tenant_id', '=', 'history.tenant_id');
            })
            ->leftJoin('warehouse_locations', static function ($join): void {
                $join->on('warehouse_locations.id', '=', 'history.warehouse_location_id')
                    ->on('warehouse_locations.tenant_id', '=', 'history.tenant_id');
            })
            ->leftJoin('inventory_batches', static function ($join): void {
                $join->on('inventory_batches.id', '=', 'history.batch_id')
                    ->on('inventory_batches.tenant_id', '=', 'history.tenant_id');
            })
            ->leftJoin('unit_of_measures', static function ($join): void {
                $join->on('unit_of_measures.id', '=', 'items.base_uom_id')
                    ->on('unit_of_measures.tenant_id', '=', 'history.tenant_id');
            })
            ->select([
                'history.tenant_id',
                'history.organization_unit_id',
                'history.item_id',
                'history.item_variant_id',
                'history.warehouse_id',
                'history.warehouse_location_id',
                'history.batch_id',
                'items.name as item_name',
                'items.code as item_code',
                'items.sku as item_sku',
                'items.reorder_level',
                'warehouses.name as warehouse_name',
                'warehouse_locations.name as location_name',
                'inventory_batches.batch_number',
                'inventory_batches.lot_number',
                'items.base_uom_id',
                'unit_of_measures.name as uom_name',
                'unit_of_measures.code as uom_code',
                'unit_of_measures.symbol as uom_symbol',
            ]);

        $onHandExpression = implode(' + ', array_map(
            static fn (InventoryStockState $state): string => 'history.'.$state->value.'_quantity',
            self::ON_HAND_STATES,
        )).' + history.reversed_to_available_quantity';
        $query->selectRaw("{$onHandExpression} AS quantity_on_hand")
            ->selectRaw('(history.available_quantity + history.reversed_to_available_quantity) AS quantity_available')
            ->selectRaw('history.reserved_quantity AS quantity_reserved')
            ->selectRaw('history.allocated_quantity AS quantity_allocated')
            ->selectRaw('history.returned_quantity AS quantity_returned')
            ->selectRaw('history.damaged_quantity AS quantity_damaged')
            ->selectRaw('history.quarantine_quantity AS quantity_quarantine')
            ->selectRaw('history.expired_quantity AS quantity_expired')
            ->selectRaw('history.scrapped_quantity AS quantity_scrapped');

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(static fn (Builder $searchQuery): Builder => $searchQuery
                ->where('items.name', 'like', $search)
                ->orWhere('items.code', 'like', $search)
                ->orWhere('items.sku', 'like', $search));
        }

        return $query;
    }

    private function summary(Builder $query): array
    {
        $available = 'history.available_quantity';
        $reorder = 'items.reorder_level';
        $availableWithReversals = "({$available} + history.reversed_to_available_quantity)";
        $summary = $query->select([])->selectRaw(
            "SUM(CASE WHEN {$availableWithReversals} > 0 AND ({$reorder} IS NULL OR {$availableWithReversals} > {$reorder}) THEN 1 ELSE 0 END) AS in_stock, "
            ."SUM(CASE WHEN {$availableWithReversals} > 0 AND {$reorder} IS NOT NULL AND {$availableWithReversals} <= {$reorder} THEN 1 ELSE 0 END) AS low_stock, "
            ."SUM(CASE WHEN {$availableWithReversals} <= 0 THEN 1 ELSE 0 END) AS out_of_stock",
        )->first();

        return [
            InventoryStockLevel::IN_STOCK->value => (int) ($summary->in_stock ?? 0),
            InventoryStockLevel::LOW_STOCK->value => (int) ($summary->low_stock ?? 0),
            InventoryStockLevel::OUT_OF_STOCK->value => (int) ($summary->out_of_stock ?? 0),
        ];
    }

    private function applyStockLevel(Builder $query, InventoryLookupRequest $request): void
    {
        if (! $request->filled('stock_level')) {
            return;
        }

        $stockLevel = InventoryStockLevel::from((string) $request->input('stock_level'));
        $available = '(history.available_quantity + history.reversed_to_available_quantity)';
        if ($stockLevel === InventoryStockLevel::OUT_OF_STOCK) {
            $query->whereRaw($available.' <= 0');

            return;
        }

        $query->whereRaw($available.' > 0');
        if ($stockLevel === InventoryStockLevel::LOW_STOCK) {
            $query->whereNotNull('items.reorder_level')
                ->whereRaw($available.' <= items.reorder_level');

            return;
        }

        $query->where(static fn (Builder $reorderQuery): Builder => $reorderQuery
            ->whereNull('items.reorder_level')
            ->orWhereRaw($available.' > items.reorder_level'));
    }

    private function balance(object $row): InventoryStockBalance
    {
        $balance = new InventoryStockBalance;
        $balance->setRawAttributes([
            'id' => (int) hexdec(substr(hash('sha256', implode('|', [
                $row->tenant_id,
                $row->organization_unit_id ?? '',
                $row->item_id,
                $row->item_variant_id ?? '',
                $row->warehouse_id,
                $row->warehouse_location_id ?? '',
                $row->batch_id ?? '',
            ])), 0, 13)),
            'tenant_id' => $row->tenant_id,
            'organization_unit_id' => $row->organization_unit_id,
            'item_id' => $row->item_id,
            'item_variant_id' => $row->item_variant_id,
            'warehouse_id' => $row->warehouse_id,
            'warehouse_location_id' => $row->warehouse_location_id,
            'batch_id' => $row->batch_id,
            'base_uom_id' => $row->base_uom_id,
            'quantity_on_hand' => $row->quantity_on_hand,
            'quantity_reserved' => $row->quantity_reserved,
            'quantity_allocated' => $row->quantity_allocated,
            'quantity_available' => $row->quantity_available,
            'quantity_returned' => $row->quantity_returned,
            'quantity_damaged' => $row->quantity_damaged,
            'quantity_quarantine' => $row->quantity_quarantine,
            'quantity_expired' => $row->quantity_expired,
            'quantity_scrapped' => $row->quantity_scrapped,
        ], true);

        $item = new Item;
        $item->setRawAttributes([
            'id' => $row->item_id,
            'name' => $row->item_name,
            'code' => $row->item_code,
            'sku' => $row->item_sku,
            'reorder_level' => $row->reorder_level,
        ], true);
        $warehouse = new WarehouseModel;
        $warehouse->setRawAttributes(['id' => $row->warehouse_id, 'name' => $row->warehouse_name], true);
        $balance->setRelation('item', $item);
        $balance->setRelation('warehouse', $warehouse);

        if ($row->warehouse_location_id !== null) {
            $location = new WarehouseLocationModel;
            $location->setRawAttributes(['id' => $row->warehouse_location_id, 'name' => $row->location_name], true);
            $balance->setRelation('warehouseLocation', $location);
        }

        if ($row->batch_id !== null) {
            $batch = new InventoryBatch;
            $batch->setRawAttributes([
                'id' => $row->batch_id,
                'batch_number' => $row->batch_number,
                'lot_number' => $row->lot_number,
            ], true);
            $balance->setRelation('batch', $batch);
        }

        if ($row->base_uom_id !== null) {
            $uom = new UnitOfMeasureModel;
            $uom->setRawAttributes([
                'id' => $row->base_uom_id,
                'name' => $row->uom_name,
                'code' => $row->uom_code,
                'symbol' => $row->uom_symbol,
            ], true);
            $balance->setRelation('baseUom', $uom);
        }

        return $balance;
    }
}
