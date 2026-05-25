<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceInspectionModel;

final class EloquentVehicleServiceInspectionRepository extends EloquentRepository implements VehicleServiceInspectionRepositoryInterface
{
    public function __construct(VehicleServiceInspectionModel $model)
    {
        parent::__construct($model);
    }
}