<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorRunningCharts;

use Modules\Core\Application\Results\Result;

interface UpdateVehicleRentalLessorRunningChartServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
