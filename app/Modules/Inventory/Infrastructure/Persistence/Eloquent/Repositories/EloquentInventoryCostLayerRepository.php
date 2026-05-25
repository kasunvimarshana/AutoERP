<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\InventoryCostLayerRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;

final class EloquentInventoryCostLayerRepository extends EloquentRepository implements InventoryCostLayerRepositoryInterface
{
    public function __construct(InventoryCostLayerModel $model)
    {
        parent::__construct($model);
    }
}