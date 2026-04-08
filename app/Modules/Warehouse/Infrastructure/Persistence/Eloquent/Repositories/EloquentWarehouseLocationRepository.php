<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Contracts\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class EloquentWarehouseLocationRepository extends EloquentRepository implements WarehouseLocationRepositoryInterface
{
    public function __construct(WarehouseLocationModel $model)
    {
        parent::__construct($model);
    }

    public function findByWarehouse(string $warehouseId): Collection
    {
        return $this->model->newQuery()->where('warehouse_id', $warehouseId)->get();
    }

    public function findChildren(string $locationId): Collection
    {
        return $this->model->newQuery()->where('parent_id', $locationId)->get();
    }
}
