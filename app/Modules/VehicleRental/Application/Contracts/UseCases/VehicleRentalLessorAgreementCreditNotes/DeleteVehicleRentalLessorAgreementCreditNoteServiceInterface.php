<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementCreditNotes;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleRentalLessorAgreementCreditNoteServiceInterface
{
    public function execute(int|string $id): Result;
}