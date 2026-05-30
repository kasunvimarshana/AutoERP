<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Repositories;

use Modules\Core\Application\Contracts\RepositoryPortInterface;
use Modules\Core\Application\DTO\DataRecord;

interface VehicleOwnershipRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listForVehicle(int $tenantId, int $vehicleId): array;

    public function findForVehicle(int $tenantId, int $vehicleId, int $ownershipId): ?DataRecord;

    public function currentForVehicleRole(int $tenantId, int $vehicleId, string $ownershipRole): ?DataRecord;

    public function vehicleExistsInTenant(int $tenantId, int $vehicleId): bool;

    public function ownerReferenceExists(string $ownerType, int $ownerId, int $tenantId): bool;

    public function clearCurrentRole(int $tenantId, int $vehicleId, string $ownershipRole, ?int $exceptOwnershipId = null): void;
}
