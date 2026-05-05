<?php declare(strict_types=1);

namespace Modules\Driver\Domain\RepositoryInterfaces;

use Modules\Driver\Domain\Entities\Driver;

interface DriverRepositoryInterface
{
    public function create(Driver $driver): void;
    public function findById(string $id): ?Driver;
    public function findByEmail(string $email): ?Driver;
    public function getAllByTenant(string $tenantId, int $page = 1, int $limit = 50): array;
    public function getActive(string $tenantId): array;
    public function getByStatus(string $tenantId, string $status): array;
    public function update(Driver $driver): void;
    public function delete(string $id): void;
    public function countByTenant(string $tenantId): int;
}
