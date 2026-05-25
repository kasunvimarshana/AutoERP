<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierVehicleRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierVehicleModel;

final class EloquentSupplierVehicleRepository extends EloquentRepository implements SupplierVehicleRepositoryInterface
{
    public function __construct(SupplierVehicleModel $model)
    {
        parent::__construct($model);
    }
}