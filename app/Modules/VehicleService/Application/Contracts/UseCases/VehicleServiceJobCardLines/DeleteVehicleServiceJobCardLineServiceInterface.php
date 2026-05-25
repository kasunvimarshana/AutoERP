<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCardLines;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleServiceJobCardLineServiceInterface
{
    public function execute(int|string $id): Result;
}