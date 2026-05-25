<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems;

use Modules\Core\Application\Results\Result;

interface CreateVehicleServiceLaborItemServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}