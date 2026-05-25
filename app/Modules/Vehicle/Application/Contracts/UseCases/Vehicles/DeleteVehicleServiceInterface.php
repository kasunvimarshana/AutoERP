<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts\UseCases\Vehicles;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleServiceInterface
{
    public function execute(int|string $id): Result;
}
