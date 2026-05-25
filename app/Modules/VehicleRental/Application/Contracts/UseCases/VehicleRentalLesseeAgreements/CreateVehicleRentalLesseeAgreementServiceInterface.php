<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreements;

use Modules\Core\Application\Results\Result;

interface CreateVehicleRentalLesseeAgreementServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}