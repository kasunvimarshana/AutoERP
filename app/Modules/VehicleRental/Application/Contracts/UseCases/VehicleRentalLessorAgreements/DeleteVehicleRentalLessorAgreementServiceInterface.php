<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreements;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleRentalLessorAgreementServiceInterface
{
    public function execute(int|string $id): Result;
}