<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes;

use Modules\Core\Application\Results\Result;

interface CreateVehicleServiceTypeServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}