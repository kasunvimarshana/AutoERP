<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceTypeRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceTypeModel;

final class EloquentVehicleServiceTypeRepository extends EloquentRepository implements VehicleServiceTypeRepositoryInterface
{
    public function __construct(VehicleServiceTypeModel $model)
    {
        parent::__construct($model);
    }
}