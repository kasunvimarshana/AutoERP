<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleServiceInspectionServiceInterface
{
    public function execute(int|string $id): Result;
}