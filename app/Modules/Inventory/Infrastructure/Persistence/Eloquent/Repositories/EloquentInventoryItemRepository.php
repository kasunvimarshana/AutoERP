<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryItemRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryItemModel;

class EloquentInventoryItemRepository extends EloquentRepository implements InventoryItemRepositoryInterface
{
    public function __construct(InventoryItemModel $model)
    {
        parent::__construct($model);
    }

    public function findByProductWarehouse(string $productId, string $warehouseId, ?string $variantId = null): mixed
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->first();
    }

    public function findByProduct(string $productId): Collection
    {
        return $this->model->newQuery()->where('product_id', $productId)->get();
    }
}
