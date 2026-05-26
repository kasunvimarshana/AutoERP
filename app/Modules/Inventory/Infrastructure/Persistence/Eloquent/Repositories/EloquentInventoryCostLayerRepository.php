<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\InventoryCostLayerRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryDimension;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;

final class EloquentInventoryCostLayerRepository extends EloquentRepository implements InventoryCostLayerRepositoryInterface
{
    public function __construct(InventoryCostLayerModel $model)
    {
        parent::__construct($model);
    }

    public function listOpenLayers(array $criteria, string $valuationMethod, int $limit = 1000): array
    {
        $lotNumber = $criteria[InventoryDimension::LOT_NUMBER] ?? null;
        unset($criteria[InventoryDimension::LOT_NUMBER]);

        $query = $this->applyCriteria($this->query(), $criteria)
            ->where('quantity_remaining', '>', 0);

        if (is_string($lotNumber) && trim($lotNumber) !== '') {
            $query
                ->join('batches', 'inventory_cost_layers.batch_id', '=', 'batches.id')
                ->select('inventory_cost_layers.*')
                ->where('batches.lot_number', $lotNumber);
        }

        $method = strtolower(trim($valuationMethod));
        if ($method !== '') {
            $query->where(function ($builder) use ($method): void {
                $builder->where('valuation_method', $method)->orWhereNull('valuation_method');
            });
        }

        if ($method === InventoryValuationMethod::LIFO) {
            $query->orderByDesc('layer_date')->orderByDesc('id');
        } else {
            $query->orderBy('layer_date')->orderBy('id');
        }

        $models = $query->limit(max(1, $limit))->get();
        $records = [];
        foreach ($models as $model) {
            $records[] = $this->toRecord($model);
        }

        return $records;
    }
}
