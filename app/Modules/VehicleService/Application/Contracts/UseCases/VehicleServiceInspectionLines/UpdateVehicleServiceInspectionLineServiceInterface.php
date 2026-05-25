<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines;

use Modules\Core\Application\Results\Result;

interface UpdateVehicleServiceInspectionLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}