<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeRunningChartRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeRunningChartModel;

final class EloquentVehicleRentalLesseeRunningChartRepository extends EloquentRepository implements VehicleRentalLesseeRunningChartRepositoryInterface
{
    public function __construct(VehicleRentalLesseeRunningChartModel $model)
    {
        parent::__construct($model);
    }
}