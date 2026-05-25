<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes;

use Modules\Core\Application\Results\Result;

interface GetVehicleServiceTypeServiceInterface
{
    public function execute(int|string $id): Result;
}