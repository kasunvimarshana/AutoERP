<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobStatusHistoryRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobStatusHistoryModel;

final class EloquentVehicleServiceJobStatusHistoryRepository extends EloquentRepository implements VehicleServiceJobStatusHistoryRepositoryInterface
{
    public function __construct(VehicleServiceJobStatusHistoryModel $model)
    {
        parent::__construct($model);
    }
}
