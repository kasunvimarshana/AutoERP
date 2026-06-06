<?php

declare(strict_types=1);

namespace Modules\Warehouse\Repositories;

use Modules\Core\Repositories\EloquentRepository;
use Modules\Warehouse\Models\WarehouseLocationModel;

final class EloquentWarehouseLocationRepository extends EloquentRepository implements WarehouseLocationRepositoryInterface
{
    public function __construct(WarehouseLocationModel $model)
    {
        parent::__construct($model);
    }
}
