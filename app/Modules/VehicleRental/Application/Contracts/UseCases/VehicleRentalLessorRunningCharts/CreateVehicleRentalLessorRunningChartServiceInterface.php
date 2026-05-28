<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts;

use Modules\Core\Application\Results\Result;

interface CreateVehicleRentalLessorRunningChartServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
