<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Vehicle\Domain\Entities\Vehicle;

interface VehicleRepositoryInterface extends RepositoryInterface
{
    public function save(Vehicle $vehicle): Vehicle;

    public function findByTenantAndVin(int $tenantId, string $vin): ?Vehicle;

    public function existsActiveRental(int $tenantId, int $vehicleId): bool;

    public function existsOpenJobCard(int $tenantId, int $vehicleId): bool;
}
