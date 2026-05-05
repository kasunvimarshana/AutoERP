<?php

declare(strict_types=1);

namespace Modules\Driver\Application\Contracts;

use Modules\Driver\Application\DTOs\CreateDriverDTO;
use Modules\Driver\Application\DTOs\UpdateDriverDTO;
use Modules\Driver\Domain\Entities\Driver;
use Modules\Driver\Domain\ValueObjects\DriverStatus;

interface DriverServiceInterface
{
    public function getById(int $id): Driver;

    /** @return Driver[] */
    public function listByTenant(int $tenantId, ?int $orgUnitId = null): array;

    /** @return Driver[] */
    public function listAvailableForTrip(int $tenantId, ?int $orgUnitId = null): array;

    public function create(CreateDriverDTO $dto): Driver;

    public function update(int $id, UpdateDriverDTO $dto): Driver;

    public function changeStatus(int $id, DriverStatus $status): Driver;

    public function delete(int $id): void;
}
