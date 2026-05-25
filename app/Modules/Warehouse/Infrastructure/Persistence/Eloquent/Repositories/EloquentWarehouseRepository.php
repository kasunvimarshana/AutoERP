<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Warehouse\Application\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

final class EloquentWarehouseRepository extends EloquentRepository implements WarehouseRepositoryInterface
{
    public function __construct(WarehouseModel $model)
    {
        parent::__construct($model);
    }
}