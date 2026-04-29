<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\RepositoryInterfaces;

use Modules\Vehicle\Domain\Entities\VehicleRental;

interface VehicleRentalRepositoryInterface
{
    public function create(array $data): VehicleRental;

    public function find(int $tenantId, int $rentalId): ?VehicleRental;

    public function paginate(int $tenantId, int $vehicleId, int $perPage, int $page): mixed;

    public function markStatus(int $tenantId, int $rentalId, string $status, array $extra = []): bool;
}
