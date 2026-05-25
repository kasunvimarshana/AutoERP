<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments;

use Modules\Core\Application\Results\Result;

interface DeleteVehicleServiceLaborAssignmentServiceInterface
{
    public function execute(int|string $id): Result;
}