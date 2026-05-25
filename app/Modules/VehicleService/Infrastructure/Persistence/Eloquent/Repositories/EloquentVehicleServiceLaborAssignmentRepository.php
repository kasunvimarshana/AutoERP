<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborAssignmentRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborAssignmentModel;

final class EloquentVehicleServiceLaborAssignmentRepository extends EloquentRepository implements VehicleServiceLaborAssignmentRepositoryInterface
{
    public function __construct(VehicleServiceLaborAssignmentModel $model)
    {
        parent::__construct($model);
    }
}