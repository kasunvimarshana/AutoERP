<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts;

interface CloseVehicleRentalServiceInterface
{
    public function execute(array $data): bool;
}
