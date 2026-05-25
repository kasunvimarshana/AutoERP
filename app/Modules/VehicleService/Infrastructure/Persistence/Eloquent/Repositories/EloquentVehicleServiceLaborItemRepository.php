<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborItemRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborItemModel;

final class EloquentVehicleServiceLaborItemRepository extends EloquentRepository implements VehicleServiceLaborItemRepositoryInterface
{
    public function __construct(VehicleServiceLaborItemModel $model)
    {
        parent::__construct($model);
    }
}