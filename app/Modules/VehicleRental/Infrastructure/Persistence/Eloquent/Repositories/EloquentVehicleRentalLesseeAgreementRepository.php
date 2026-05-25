<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementModel;

final class EloquentVehicleRentalLesseeAgreementRepository extends EloquentRepository implements VehicleRentalLesseeAgreementRepositoryInterface
{
    public function __construct(VehicleRentalLesseeAgreementModel $model)
    {
        parent::__construct($model);
    }
}