<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryAdjustmentRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryAdjustmentModel;

class EloquentInventoryAdjustmentRepository extends EloquentRepository implements InventoryAdjustmentRepositoryInterface
{
    public function __construct(InventoryAdjustmentModel $model)
    {
        parent::__construct($model);
    }
}
