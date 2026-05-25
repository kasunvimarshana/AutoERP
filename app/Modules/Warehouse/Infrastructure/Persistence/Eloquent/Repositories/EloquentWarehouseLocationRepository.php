<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Warehouse\Application\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

final class EloquentWarehouseLocationRepository extends EloquentRepository implements WarehouseLocationRepositoryInterface
{
    public function __construct(WarehouseLocationModel $model)
    {
        parent::__construct($model);
    }
}