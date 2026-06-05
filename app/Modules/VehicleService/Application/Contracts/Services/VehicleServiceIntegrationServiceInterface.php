<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VehicleServiceIntegrationServiceInterface
{
    public function allocateServicePayment(int $jobCardId, array $payload): Result;

    public function postServiceInventory(int $jobCardId, array $payload): Result;
}
