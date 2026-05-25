<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments;

use Modules\Core\Application\Results\Result;

interface ListVehicleServiceLaborAssignmentsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}