<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceJobCardLines;

use Modules\Core\Application\Results\Result;

interface CreateVehicleServiceJobCardLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}