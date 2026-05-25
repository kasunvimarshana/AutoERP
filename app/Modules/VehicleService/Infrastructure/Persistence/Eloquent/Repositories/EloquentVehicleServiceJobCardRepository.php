<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardModel;

final class EloquentVehicleServiceJobCardRepository extends EloquentRepository implements VehicleServiceJobCardRepositoryInterface
{
    public function __construct(VehicleServiceJobCardModel $model)
    {
        parent::__construct($model);
    }
}