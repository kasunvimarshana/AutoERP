<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\Contracts;

use Modules\Fleet\Application\DTOs\CreateVehicleTypeDTO;
use Modules\Fleet\Domain\Entities\VehicleType;

interface VehicleTypeServiceInterface
{
    public function create(CreateVehicleTypeDTO $dto): VehicleType;

    public function update(int $id, array $data): VehicleType;

    public function delete(int $id): void;

    public function find(int $id): ?VehicleType;

    /** @return list<VehicleType> */
    public function listByTenant(int $tenantId): array;
}
