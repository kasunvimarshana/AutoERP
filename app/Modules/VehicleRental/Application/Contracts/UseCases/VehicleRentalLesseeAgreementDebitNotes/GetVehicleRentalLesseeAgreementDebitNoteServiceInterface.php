<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes;

use Modules\Core\Application\Results\Result;

interface GetVehicleRentalLesseeAgreementDebitNoteServiceInterface
{
    public function execute(int|string $id): Result;
}