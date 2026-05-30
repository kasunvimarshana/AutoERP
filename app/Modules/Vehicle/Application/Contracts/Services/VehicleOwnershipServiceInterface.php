<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VehicleOwnershipServiceInterface
{
    public function list(int $tenantId, int $vehicleId): Result;

    public function current(int $tenantId, int $vehicleId, string $ownershipRole = 'legal_owner'): Result;

    /**
     * @param array<string, mixed> $payload
     */
    public function create(int $vehicleId, array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     */
    public function update(int $vehicleId, int $ownershipId, array $payload): Result;

    public function end(int $tenantId, int $vehicleId, int $ownershipId, string $endDate): Result;

    public function setCurrent(int $tenantId, int $vehicleId, int $ownershipId): Result;
}
