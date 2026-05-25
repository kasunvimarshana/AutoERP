<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardLineRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardLineModel;

final class EloquentVehicleServiceJobCardLineRepository extends EloquentRepository implements VehicleServiceJobCardLineRepositoryInterface
{
    public function __construct(VehicleServiceJobCardLineModel $model)
    {
        parent::__construct($model);
    }
}