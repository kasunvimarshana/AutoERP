<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Repositories;

use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface VehicleRepositoryInterface extends RepositoryPortInterface
{
    public function pageByFilters(
        ?int $tenantId,
        ?int $organizationUnitId,
        ?string $vehicleCode,
        ?string $vin,
        ?string $licensePlate,
        ?string $status,
        int $perPage,
        int $page,
    ): PagedResult;
}
