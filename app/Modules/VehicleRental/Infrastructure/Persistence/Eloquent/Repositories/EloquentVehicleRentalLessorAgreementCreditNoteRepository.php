<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementCreditNoteModel;

final class EloquentVehicleRentalLessorAgreementCreditNoteRepository extends EloquentRepository implements VehicleRentalLessorAgreementCreditNoteRepositoryInterface
{
    public function __construct(VehicleRentalLessorAgreementCreditNoteModel $model)
    {
        parent::__construct($model);
    }
}
