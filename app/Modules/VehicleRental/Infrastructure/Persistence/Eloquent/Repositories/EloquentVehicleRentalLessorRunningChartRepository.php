<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorRunningChartRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorRunningChartModel;

final class EloquentVehicleRentalLessorRunningChartRepository extends EloquentRepository implements VehicleRentalLessorRunningChartRepositoryInterface
{
    public function __construct(VehicleRentalLessorRunningChartModel $model)
    {
        parent::__construct($model);
    }
}
