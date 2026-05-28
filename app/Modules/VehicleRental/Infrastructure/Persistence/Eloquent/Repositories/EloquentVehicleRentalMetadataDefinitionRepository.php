<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalMetadataDefinitionRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalMetadataDefinitionModel;

final class EloquentVehicleRentalMetadataDefinitionRepository extends EloquentRepository implements VehicleRentalMetadataDefinitionRepositoryInterface
{
    public function __construct(VehicleRentalMetadataDefinitionModel $model)
    {
        parent::__construct($model);
    }
}
