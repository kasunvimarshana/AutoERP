<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts;

interface CreateVehicleJobCardServiceInterface
{
    public function execute(array $data): mixed;
}
