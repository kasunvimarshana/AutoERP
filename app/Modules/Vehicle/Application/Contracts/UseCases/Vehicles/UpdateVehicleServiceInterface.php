<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts\UseCases\Vehicles;

use Modules\Core\Application\Results\Result;

interface UpdateVehicleServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
