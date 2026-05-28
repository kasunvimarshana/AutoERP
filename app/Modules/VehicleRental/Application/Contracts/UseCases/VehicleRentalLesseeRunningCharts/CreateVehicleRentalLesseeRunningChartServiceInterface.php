<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeRunningCharts;

use Modules\Core\Application\Results\Result;

interface CreateVehicleRentalLesseeRunningChartServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
