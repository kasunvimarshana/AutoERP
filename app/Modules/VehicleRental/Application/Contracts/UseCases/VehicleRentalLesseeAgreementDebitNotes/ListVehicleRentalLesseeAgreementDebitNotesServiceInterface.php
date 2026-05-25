<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes;

use Modules\Core\Application\Results\Result;

interface ListVehicleRentalLesseeAgreementDebitNotesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}