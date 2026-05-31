<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
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
        ?string $search,
        ?string $status,
        ?bool $serviceEnabled,
        ?bool $rentalEnabled,
        int $perPage,
        int $page,
    ): PagedResult;

    public function findInTenant(int $tenantId, int|string $id): ?DataRecord;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateInTenant(int $tenantId, int|string $id, array $attributes): DataRecord;

    public function deleteInTenant(int $tenantId, int|string $id): bool;
}
