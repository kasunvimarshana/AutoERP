<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalStatusHistoryRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalStatusHistoryModel;

final class EloquentVehicleRentalStatusHistoryRepository extends EloquentRepository implements VehicleRentalStatusHistoryRepositoryInterface
{
    public function __construct(VehicleRentalStatusHistoryModel $model)
    {
        parent::__construct($model);
    }
}
