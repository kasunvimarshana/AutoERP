<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Repositories;

use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface VehicleDocumentRepositoryInterface extends RepositoryPortInterface
{
    public function pageByFilters(
        ?int $tenantId,
        ?int $organizationUnitId,
        ?int $vehicleId,
        ?string $name,
        ?string $type,
        int $perPage,
        int $page,
    ): PagedResult;
}
