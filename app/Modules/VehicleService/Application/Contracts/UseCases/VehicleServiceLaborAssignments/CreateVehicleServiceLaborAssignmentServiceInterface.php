<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments;

use Modules\Core\Application\Results\Result;

interface CreateVehicleServiceLaborAssignmentServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}