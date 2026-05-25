<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts;

use Modules\Core\Application\Results\Result;

interface GetVehicleRentalLesseeRunningChartServiceInterface
{
    public function execute(int|string $id): Result;
}