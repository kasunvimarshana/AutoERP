<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalRunningChartRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalRunningChartModel;

final class EloquentVehicleRentalRunningChartRepository extends EloquentRepository implements VehicleRentalRunningChartRepositoryInterface
{
    public function __construct(VehicleRentalRunningChartModel $model)
    {
        parent::__construct($model);
    }
}
