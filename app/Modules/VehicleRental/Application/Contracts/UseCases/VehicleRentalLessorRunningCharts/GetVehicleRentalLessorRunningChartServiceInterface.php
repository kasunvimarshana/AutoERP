<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts;

use Modules\Core\Application\Results\Result;

interface GetVehicleRentalLessorRunningChartServiceInterface
{
    public function execute(int|string $id): Result;
}