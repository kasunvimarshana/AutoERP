<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts\UseCases\Vehicles;

use Modules\Core\Application\Results\Result;

interface CreateVehicleServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
