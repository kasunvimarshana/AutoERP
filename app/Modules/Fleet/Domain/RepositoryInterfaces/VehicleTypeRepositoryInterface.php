<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\RepositoryInterfaces;

use Modules\Fleet\Domain\Entities\VehicleType;

interface VehicleTypeRepositoryInterface
{
    public function find(int $id): ?VehicleType;

    /** @return list<VehicleType> */
    public function listByTenant(int $tenantId): array;

    public function save(VehicleType $type): VehicleType;

    public function delete(int $id): void;
}
