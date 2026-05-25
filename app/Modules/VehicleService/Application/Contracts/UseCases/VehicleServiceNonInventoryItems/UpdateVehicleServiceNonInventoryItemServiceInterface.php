<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems;

use Modules\Core\Application\Results\Result;

interface UpdateVehicleServiceNonInventoryItemServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}