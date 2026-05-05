<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\RepositoryInterfaces;

use Modules\Fleet\Domain\Entities\Vehicle;

interface VehicleRepositoryInterface
{
    public function find(int $id): ?Vehicle;

    public function findByRegistration(int $tenantId, string $registration): ?Vehicle;

    /** @return list<Vehicle> */
    public function listByTenant(int $tenantId, array $filters = []): array;

    /** @return list<Vehicle> */
    public function listAvailableForRental(int $tenantId): array;

    /** @return list<Vehicle> */
    public function listAvailableForService(int $tenantId): array;

    public function save(Vehicle $vehicle): Vehicle;

    public function updateState(int $vehicleId, string $newState, string $updatedAt): void;

    public function updateOdometer(int $vehicleId, string $odometer): void;

    public function delete(int $id): void;
}
