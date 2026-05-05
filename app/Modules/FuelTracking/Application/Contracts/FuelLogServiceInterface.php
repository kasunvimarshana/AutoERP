<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Application\Contracts;

use Modules\FuelTracking\Application\DTOs\CreateFuelLogDTO;
use Modules\FuelTracking\Domain\Entities\FuelLog;

interface FuelLogServiceInterface
{
    public function createLog(CreateFuelLogDTO $dto): FuelLog;

    public function getLog(string $id): FuelLog;

    /** @return FuelLog[] */
    public function getByTenant(string $tenantId, string $orgUnitId): array;

    /** @return FuelLog[] */
    public function getByVehicle(string $tenantId, string $vehicleId): array;

    /** @return FuelLog[] */
    public function getByDriver(string $tenantId, string $driverId): array;

    public function deleteLog(string $id): void;
}
