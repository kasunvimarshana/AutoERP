<?php declare(strict_types=1);

namespace Modules\Driver\Application\Contracts;

use Modules\Driver\Domain\Entities\License;

interface ManageLicenseServiceInterface
{
    public function create(array $data): License;

    public function update(int $tenantId, string $id, array $data): License;

    public function find(int $tenantId, string $id): License;

    public function delete(int $tenantId, string $id): void;

    public function getByDriver(int $tenantId, string $driverId): array;

    public function getExpiring(int $tenantId, int $daysThreshold = 30): array;
}
