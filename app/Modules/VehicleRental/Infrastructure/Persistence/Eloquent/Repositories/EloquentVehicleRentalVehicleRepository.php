<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalVehicleRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalVehicleModel;

final class EloquentVehicleRentalVehicleRepository extends EloquentRepository implements VehicleRentalVehicleRepositoryInterface
{
    public function __construct(VehicleRentalVehicleModel $model)
    {
        parent::__construct($model);
    }
}
