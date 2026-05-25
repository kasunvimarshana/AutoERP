<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleServiceLaborItemServiceInterface
{
    public function execute(int|string $id): Result;
}