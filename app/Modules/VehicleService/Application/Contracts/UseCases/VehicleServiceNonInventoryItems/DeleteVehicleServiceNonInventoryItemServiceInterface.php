<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleServiceNonInventoryItemServiceInterface
{
    public function execute(int|string $id): Result;
}