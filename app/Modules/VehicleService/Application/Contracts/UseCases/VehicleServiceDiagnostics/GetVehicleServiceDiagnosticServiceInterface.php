<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics;

use Modules\Core\Application\Results\Result;

interface GetVehicleServiceDiagnosticServiceInterface
{
    public function execute(int|string $id): Result;
}