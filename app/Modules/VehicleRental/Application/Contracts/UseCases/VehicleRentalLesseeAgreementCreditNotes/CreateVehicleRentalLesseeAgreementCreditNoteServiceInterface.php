<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementCreditNotes;

use Modules\Core\Application\Results\Result;

interface CreateVehicleRentalLesseeAgreementCreditNoteServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}