<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalAgreementRateRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalAgreementRateModel;

final class EloquentVehicleRentalAgreementRateRepository extends EloquentRepository implements VehicleRentalAgreementRateRepositoryInterface
{
    public function __construct(VehicleRentalAgreementRateModel $model)
    {
        parent::__construct($model);
    }
}
