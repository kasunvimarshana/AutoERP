<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalBreakdownRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalBreakdownModel;

final class EloquentVehicleRentalBreakdownRepository extends EloquentRepository implements VehicleRentalBreakdownRepositoryInterface
{
    public function __construct(VehicleRentalBreakdownModel $model)
    {
        parent::__construct($model);
    }
}
