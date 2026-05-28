<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalMetadataValueRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalMetadataValueModel;

final class EloquentVehicleRentalMetadataValueRepository extends EloquentRepository implements VehicleRentalMetadataValueRepositoryInterface
{
    public function __construct(VehicleRentalMetadataValueModel $model)
    {
        parent::__construct($model);
    }
}
