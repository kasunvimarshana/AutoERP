<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionLineRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceInspectionLineModel;

final class EloquentVehicleServiceInspectionLineRepository extends EloquentRepository implements VehicleServiceInspectionLineRepositoryInterface
{
    public function __construct(VehicleServiceInspectionLineModel $model)
    {
        parent::__construct($model);
    }
}