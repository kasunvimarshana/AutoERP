<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalReplacementRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalReplacementModel;

final class EloquentVehicleRentalReplacementRepository extends EloquentRepository implements VehicleRentalReplacementRepositoryInterface
{
    public function __construct(VehicleRentalReplacementModel $model)
    {
        parent::__construct($model);
    }
}
