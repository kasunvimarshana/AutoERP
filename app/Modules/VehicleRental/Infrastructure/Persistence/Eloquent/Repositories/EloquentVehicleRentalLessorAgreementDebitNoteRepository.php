<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Models\VehicleRentalLessorAgreementDebitNoteModel;

final class EloquentVehicleRentalLessorAgreementDebitNoteRepository extends EloquentRepository implements VehicleRentalLessorAgreementDebitNoteRepositoryInterface
{
    public function __construct(VehicleRentalLessorAgreementDebitNoteModel $model)
    {
        parent::__construct($model);
    }
}