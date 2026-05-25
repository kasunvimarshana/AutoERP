<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleServiceInspectionLineServiceInterface
{
    public function execute(int|string $id): Result;
}