<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryMovementRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryMovementModel;

class EloquentInventoryMovementRepository extends EloquentRepository implements InventoryMovementRepositoryInterface
{
    public function __construct(InventoryMovementModel $model)
    {
        parent::__construct($model);
    }

    public function findByProduct(string $productId): Collection
    {
        return $this->model->newQuery()->where('product_id', $productId)->orderBy('created_at', 'desc')->get();
    }
}
