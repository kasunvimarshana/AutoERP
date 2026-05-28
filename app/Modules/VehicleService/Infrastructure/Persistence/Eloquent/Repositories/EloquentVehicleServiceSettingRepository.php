<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceSettingRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceSettingModel;

final class EloquentVehicleServiceSettingRepository extends EloquentRepository implements VehicleServiceSettingRepositoryInterface
{
    public function __construct(VehicleServiceSettingModel $model)
    {
        parent::__construct($model);
    }
}
