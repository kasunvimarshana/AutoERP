<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines;

use Modules\Core\Application\Results\Result;

interface CreateVehicleServiceInspectionLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}