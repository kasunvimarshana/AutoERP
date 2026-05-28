<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobInventoryLinkRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobInventoryLinkModel;

final class EloquentVehicleServiceJobInventoryLinkRepository extends EloquentRepository implements VehicleServiceJobInventoryLinkRepositoryInterface
{
    public function __construct(VehicleServiceJobInventoryLinkModel $model)
    {
        parent::__construct($model);
    }
}
