<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalAgreementLineRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalAgreementLineModel;

final class EloquentVehicleRentalAgreementLineRepository extends EloquentRepository implements VehicleRentalAgreementLineRepositoryInterface
{
    public function __construct(VehicleRentalAgreementLineModel $model)
    {
        parent::__construct($model);
    }
}
