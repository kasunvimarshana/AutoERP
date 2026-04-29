<?php

declare(strict_types=1);

namespace Modules\Vehicle\Domain\RepositoryInterfaces;

use Modules\Vehicle\Domain\Entities\VehicleJobCard;

interface VehicleJobCardRepositoryInterface
{
    public function create(array $data): VehicleJobCard;

    public function find(int $tenantId, int $jobCardId): ?VehicleJobCard;

    public function paginate(int $tenantId, int $vehicleId, int $perPage, int $page): mixed;

    public function markStatus(int $tenantId, int $jobCardId, string $status): bool;
}
