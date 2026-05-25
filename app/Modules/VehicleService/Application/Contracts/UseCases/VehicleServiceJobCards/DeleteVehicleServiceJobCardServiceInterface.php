<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCards;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleServiceJobCardServiceInterface
{
    public function execute(int|string $id): Result;
}