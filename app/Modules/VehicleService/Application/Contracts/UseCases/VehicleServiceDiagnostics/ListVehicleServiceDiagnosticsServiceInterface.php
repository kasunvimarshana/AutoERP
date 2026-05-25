<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics;

use Modules\Core\Application\Results\Result;

interface ListVehicleServiceDiagnosticsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}