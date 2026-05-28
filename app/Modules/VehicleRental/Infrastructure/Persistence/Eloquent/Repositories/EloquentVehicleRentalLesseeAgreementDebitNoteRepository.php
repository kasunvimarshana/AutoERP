<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementDebitNoteModel;

final class EloquentVehicleRentalLesseeAgreementDebitNoteRepository extends EloquentRepository implements VehicleRentalLesseeAgreementDebitNoteRepositoryInterface
{
    public function __construct(VehicleRentalLesseeAgreementDebitNoteModel $model)
    {
        parent::__construct($model);
    }
}
