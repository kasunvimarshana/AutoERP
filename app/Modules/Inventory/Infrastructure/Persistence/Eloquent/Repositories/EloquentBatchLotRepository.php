<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Contracts\Repositories\BatchLotRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchLotModel;

class EloquentBatchLotRepository extends EloquentRepository implements BatchLotRepositoryInterface
{
    public function __construct(BatchLotModel $model)
    {
        parent::__construct($model);
    }

    public function findByBatchNumber(string $productId, string $batchNumber): mixed
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->where('batch_number', $batchNumber)
            ->first();
    }

    /**
     * Return available batch/lot records (quantity > 0) for allocation.
     */
    public function findAvailableForAllocation(
        string $productId,
        string $warehouseId,
        ?string $variantId = null,
    ): Collection {
        $query = $this->model->newQuery()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        }

        return $query->get();
    }
}
