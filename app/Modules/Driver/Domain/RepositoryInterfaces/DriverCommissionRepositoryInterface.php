<?php declare(strict_types=1);

namespace Modules\Driver\Domain\RepositoryInterfaces;

use Modules\Driver\Domain\Entities\DriverCommission;

interface DriverCommissionRepositoryInterface
{
    public function create(DriverCommission $commission): void;
    public function findById(string $id): ?DriverCommission;
    public function getByDriver(string $driverId, int $page = 1, int $limit = 50): array;
    public function getByStatus(string $tenantId, string $status): array;
    public function getPendingByDriver(string $driverId): array;
    public function update(DriverCommission $commission): void;
    public function delete(string $id): void;
}
