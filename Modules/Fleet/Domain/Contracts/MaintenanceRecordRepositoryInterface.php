<?php

namespace Modules\Fleet\Domain\Contracts;

interface MaintenanceRecordRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByVehicle(string $vehicleId): iterable;
    public function create(array $data): object;
    public function delete(string $id): void;
}
