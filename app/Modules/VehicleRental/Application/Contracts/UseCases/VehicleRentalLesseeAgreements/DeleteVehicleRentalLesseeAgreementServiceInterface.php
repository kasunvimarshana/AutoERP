<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleRentalLesseeAgreementServiceInterface
{
    public function execute(int|string $id): Result;
}