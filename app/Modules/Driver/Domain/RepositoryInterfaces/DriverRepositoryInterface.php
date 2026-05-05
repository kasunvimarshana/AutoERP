<?php

declare(strict_types=1);

namespace Modules\Driver\Domain\RepositoryInterfaces;

use Modules\Driver\Domain\Entities\Driver;
use Modules\Driver\Domain\ValueObjects\DriverStatus;

interface DriverRepositoryInterface
{
    public function findById(int $id): ?Driver;

    /** @return Driver[] */
    public function findByTenant(int $tenantId, ?int $orgUnitId = null): array;

    /** @return Driver[] */
    public function findAvailableForTrip(int $tenantId, ?int $orgUnitId = null): array;

    public function save(Driver $driver): Driver;

    public function updateStatus(int $id, DriverStatus $status): void;

    public function delete(int $id): void;
}
