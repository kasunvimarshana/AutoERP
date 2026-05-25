<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceDiagnosticLines;

use Modules\Core\Application\Results\Result;

interface CreateVehicleServiceDiagnosticLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}