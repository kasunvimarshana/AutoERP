<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalSettingRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalSettingModel;

final class EloquentVehicleRentalSettingRepository extends EloquentRepository implements VehicleRentalSettingRepositoryInterface
{
    public function __construct(VehicleRentalSettingModel $model)
    {
        parent::__construct($model);
    }
}
