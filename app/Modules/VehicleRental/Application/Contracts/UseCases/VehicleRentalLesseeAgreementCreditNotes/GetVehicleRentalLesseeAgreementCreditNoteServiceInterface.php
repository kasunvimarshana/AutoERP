<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementCreditNotes;

use Modules\Core\Application\Results\Result;

interface GetVehicleRentalLesseeAgreementCreditNoteServiceInterface
{
    public function execute(int|string $id): Result;
}
