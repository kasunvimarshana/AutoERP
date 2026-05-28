<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLesseeAgreementCreditNoteModel;

final class EloquentVehicleRentalLesseeAgreementCreditNoteRepository extends EloquentRepository implements VehicleRentalLesseeAgreementCreditNoteRepositoryInterface
{
    public function __construct(VehicleRentalLesseeAgreementCreditNoteModel $model)
    {
        parent::__construct($model);
    }
}
