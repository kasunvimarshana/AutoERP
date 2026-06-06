<?php

declare(strict_types=1);

namespace Modules\Warehouse\Repositories;

use Modules\Core\Repositories\EloquentRepository;
use Modules\Warehouse\Models\WarehouseModel;

final class EloquentWarehouseRepository extends EloquentRepository implements WarehouseRepositoryInterface
{
    public function __construct(WarehouseModel $model)
    {
        parent::__construct($model);
    }
}
