<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes;

use Modules\Core\Application\Results\Result;

interface CreateVehicleRentalLessorAgreementDebitNoteServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}