<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementModel;

final class EloquentVehicleRentalLessorAgreementRepository extends EloquentRepository implements VehicleRentalLessorAgreementRepositoryInterface
{
    public function __construct(VehicleRentalLessorAgreementModel $model)
    {
        parent::__construct($model);
    }
}
