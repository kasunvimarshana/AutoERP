<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleService\Application\Repositories\VehicleServiceNonInventoryItemRepositoryInterface;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceNonInventoryItemModel;

final class EloquentVehicleServiceNonInventoryItemRepository extends EloquentRepository implements VehicleServiceNonInventoryItemRepositoryInterface
{
    public function __construct(VehicleServiceNonInventoryItemModel $model)
    {
        parent::__construct($model);
    }
}