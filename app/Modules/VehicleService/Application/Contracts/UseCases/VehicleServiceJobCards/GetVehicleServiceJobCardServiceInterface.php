<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards;

use Modules\Core\Application\Results\Result;

interface GetVehicleServiceJobCardServiceInterface
{
    public function execute(int|string $id): Result;
}