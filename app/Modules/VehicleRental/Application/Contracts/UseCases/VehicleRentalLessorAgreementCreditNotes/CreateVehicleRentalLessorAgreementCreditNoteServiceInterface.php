<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementCreditNotes;

use Modules\Core\Application\Results\Result;

interface CreateVehicleRentalLessorAgreementCreditNoteServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
