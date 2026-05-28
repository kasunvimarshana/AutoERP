<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobExternalServiceRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobExternalServiceModel;

final class EloquentVehicleServiceJobExternalServiceRepository extends EloquentRepository implements VehicleServiceJobExternalServiceRepositoryInterface
{
    public function __construct(VehicleServiceJobExternalServiceModel $model)
    {
        parent::__construct($model);
    }
}
