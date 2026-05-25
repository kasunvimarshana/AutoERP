<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts;

use Modules\Core\Application\Results\Result;

interface ListVehicleRentalLessorRunningChartsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}