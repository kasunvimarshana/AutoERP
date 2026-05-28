<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalAgreementRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalAgreementModel;

final class EloquentVehicleRentalAgreementRepository extends EloquentRepository implements VehicleRentalAgreementRepositoryInterface
{
    public function __construct(VehicleRentalAgreementModel $model)
    {
        parent::__construct($model);
    }
}
