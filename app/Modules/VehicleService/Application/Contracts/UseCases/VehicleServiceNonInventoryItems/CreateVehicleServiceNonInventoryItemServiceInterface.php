<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems;

use Modules\Core\Application\Results\Result;

interface CreateVehicleServiceNonInventoryItemServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}