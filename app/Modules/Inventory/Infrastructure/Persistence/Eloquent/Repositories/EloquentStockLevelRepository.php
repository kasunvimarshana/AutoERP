<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

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
}
