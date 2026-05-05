<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Domain\RepositoryInterfaces;

use Modules\FuelTracking\Domain\Entities\FuelLog;

interface FuelLogRepositoryInterface
{
    public function findById(string $id): ?FuelLog;

    /** @return FuelLog[] */
    public function findByTenant(string $tenantId, string $orgUnitId): array;

    /** @return FuelLog[] */
    public function findByVehicle(string $tenantId, string $vehicleId): array;

    /** @return FuelLog[] */
    public function findByDriver(string $tenantId, string $driverId): array;

    public function save(FuelLog $log): FuelLog;

    public function delete(string $id): void;
}
