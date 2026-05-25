<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnostics;

use Modules\Core\Application\Results\Result;

interface UpdateVehicleServiceDiagnosticServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}