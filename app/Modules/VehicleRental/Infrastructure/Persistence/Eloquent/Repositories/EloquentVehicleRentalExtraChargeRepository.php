<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalExtraChargeRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalExtraChargeModel;

final class EloquentVehicleRentalExtraChargeRepository extends EloquentRepository implements VehicleRentalExtraChargeRepositoryInterface
{
    public function __construct(VehicleRentalExtraChargeModel $model)
    {
        parent::__construct($model);
    }
}
