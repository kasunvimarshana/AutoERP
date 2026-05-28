<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalRunningChartLineRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalRunningChartLineModel;

final class EloquentVehicleRentalRunningChartLineRepository extends EloquentRepository implements VehicleRentalRunningChartLineRepositoryInterface
{
    public function __construct(VehicleRentalRunningChartLineModel $model)
    {
        parent::__construct($model);
    }
}
