<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalProviderPayableRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalProviderPayableModel;

final class EloquentVehicleRentalProviderPayableRepository extends EloquentRepository implements VehicleRentalProviderPayableRepositoryInterface
{
    public function __construct(VehicleRentalProviderPayableModel $model)
    {
        parent::__construct($model);
    }
}
