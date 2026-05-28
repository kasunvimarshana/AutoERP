<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes;

use Modules\Core\Application\Results\Result;

interface GetVehicleRentalLessorAgreementDebitNoteServiceInterface
{
    public function execute(int|string $id): Result;
}
