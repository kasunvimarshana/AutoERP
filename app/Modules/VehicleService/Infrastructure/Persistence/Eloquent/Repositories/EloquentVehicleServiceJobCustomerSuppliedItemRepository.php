<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCustomerSuppliedItemRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCustomerSuppliedItemModel;

final class EloquentVehicleServiceJobCustomerSuppliedItemRepository extends EloquentRepository implements VehicleServiceJobCustomerSuppliedItemRepositoryInterface
{
    public function __construct(VehicleServiceJobCustomerSuppliedItemModel $model)
    {
        parent::__construct($model);
    }
}
