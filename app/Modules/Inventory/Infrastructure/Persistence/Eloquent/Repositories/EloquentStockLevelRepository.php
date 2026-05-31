<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryAllocationMethod;
use Modules\Inventory\Domain\Constants\InventoryDimension;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;

final class EloquentStockLevelRepository extends EloquentRepository implements StockLevelRepositoryInterface
{
    public function __construct(StockLevelModel $model)
    {
        parent::__construct($model);
    }

    public function page(array $criteria, int $perPage, int $page, array $with = []): PagedResult
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than zero.');
        }

        if ($perPage < 1) {
            throw new InvalidArgumentException('Per-page must be greater than zero.');
        }

        $query = $this->applyStockLevelCriteria($this->query($with)->select('stock_levels.*'), $criteria);
        $paginator = $query->paginate($perPage, ['stock_levels.*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $model) {
            $items[] = $this->toRecord($model);
        }

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function listAllocatableStock(array $criteria, string $allocationMethod, int $limit = 1000): array
    {
        $lotNumber = $criteria[InventoryDimension::LOT_NUMBER] ?? null;
        unset($criteria[InventoryDimension::LOT_NUMBER]);

        $query = $this->applyCriteria($this->query(), $criteria)
            ->whereRaw('(quantity_on_hand - quantity_reserved - quantity_blocked) > 0');

        if (is_string($lotNumber) && trim($lotNumber) !== '') {
            $query
                ->join('batches', 'stock_levels.batch_id', '=', 'batches.id')
                ->select('stock_levels.*')
                ->where('batches.lot_number', $lotNumber);
        }

        $method = strtolower(trim($allocationMethod));
        if ($method === InventoryAllocationMethod::FEFO) {
            $query
                ->leftJoin('batches', 'stock_levels.batch_id', '=', 'batches.id')
                ->select('stock_levels.*')
                ->orderByRaw('CASE WHEN batches.expiry_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('batches.expiry_date')
                ->orderBy('stock_levels.id');
        } elseif ($method === InventoryAllocationMethod::FIFO) {
            $query->orderBy('last_movement_at')->orderBy('id');
        } else {
            $query->orderByDesc('last_movement_at')->orderByDesc('id');
        }

        $models = $query->limit(max(1, $limit))->get();
        $records = [];
        foreach ($models as $model) {
            $records[] = $this->toRecord($model);
        }

        return $records;
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function applyStockLevelCriteria(Builder $query, array $criteria): Builder
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        $batchSerial = trim((string) ($criteria['batch_serial'] ?? ''));
        $lowStock = filter_var($criteria['low_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $exactCriteria = $criteria;
        unset($exactCriteria['search'], $exactCriteria['batch_serial'], $exactCriteria['low_stock'], $exactCriteria['status'], $exactCriteria['uom_id']);

        if (isset($criteria['status']) && ! isset($exactCriteria['condition'])) {
            $exactCriteria['condition'] = $criteria['status'];
        }

        if (isset($criteria['uom_id']) && ! isset($exactCriteria['base_uom_id'])) {
            $exactCriteria['base_uom_id'] = $criteria['uom_id'];
        }

        foreach ($exactCriteria as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $query->where('stock_levels.' . $column, $value);
        }

        if ($lowStock) {
            $query
                ->whereNotNull('stock_levels.minimum_quantity')
                ->whereColumn('stock_levels.quantity_on_hand', '<=', 'stock_levels.minimum_quantity');
        }

        if ($search !== '' || $batchSerial !== '') {
            $this->joinDisplayTables($query);
        }

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query->where(function (Builder $nested) use ($like): void {
                $nested
                    ->where('filter_items.sku', 'like', $like)
                    ->orWhere('filter_items.name', 'like', $like)
                    ->orWhere('filter_warehouses.code', 'like', $like)
                    ->orWhere('filter_warehouses.name', 'like', $like)
                    ->orWhere('filter_locations.code', 'like', $like)
                    ->orWhere('filter_locations.name', 'like', $like)
                    ->orWhere('filter_batches.batch_number', 'like', $like)
                    ->orWhere('filter_batches.lot_number', 'like', $like)
                    ->orWhere('filter_serials.serial_number', 'like', $like)
                    ->orWhere('filter_uoms.code', 'like', $like)
                    ->orWhere('filter_uoms.symbol', 'like', $like)
                    ->orWhere('stock_levels.condition', 'like', $like);
            });
        }

        if ($batchSerial !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $batchSerial) . '%';
            $query->where(function (Builder $nested) use ($like): void {
                $nested
                    ->where('filter_batches.batch_number', 'like', $like)
                    ->orWhere('filter_batches.lot_number', 'like', $like)
                    ->orWhere('filter_serials.serial_number', 'like', $like);
            });
        }

        return $query->orderBy('stock_levels.id');
    }

    private function joinDisplayTables(Builder $query): void
    {
        $query
            ->leftJoin('items as filter_items', 'filter_items.id', '=', 'stock_levels.item_id')
            ->leftJoin('warehouses as filter_warehouses', 'filter_warehouses.id', '=', 'stock_levels.warehouse_id')
            ->leftJoin('warehouse_locations as filter_locations', 'filter_locations.id', '=', 'stock_levels.location_id')
            ->leftJoin('batches as filter_batches', 'filter_batches.id', '=', 'stock_levels.batch_id')
            ->leftJoin('serials as filter_serials', 'filter_serials.id', '=', 'stock_levels.serial_id')
            ->leftJoin('unit_of_measures as filter_uoms', 'filter_uoms.id', '=', 'stock_levels.base_uom_id');
    }
}
