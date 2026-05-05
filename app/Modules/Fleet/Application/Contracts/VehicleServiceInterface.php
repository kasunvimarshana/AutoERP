<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\Contracts;

use Modules\Fleet\Application\DTOs\ChangeVehicleStateDTO;
use Modules\Fleet\Application\DTOs\CreateVehicleDTO;
use Modules\Fleet\Application\DTOs\UpdateVehicleDTO;
use Modules\Fleet\Domain\Entities\Vehicle;

interface VehicleServiceInterface
{
    public function create(CreateVehicleDTO $dto): Vehicle;

    public function update(UpdateVehicleDTO $dto): Vehicle;

    public function delete(int $id): void;

    public function find(int $id): ?Vehicle;

    /** @return list<Vehicle> */
    public function listByTenant(int $tenantId, array $filters = []): array;

    /** @return list<Vehicle> */
    public function listAvailableForRental(int $tenantId): array;

    /** @return list<Vehicle> */
    public function listAvailableForService(int $tenantId): array;

    public function changeState(ChangeVehicleStateDTO $dto): Vehicle;

    public function updateOdometer(int $vehicleId, string $odometer): void;
}
