<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts;

interface VehicleDashboardServiceInterface
{
    public function execute(array $data): array;
}
