<?php declare(strict_types=1);

namespace Modules\Driver\Application\Contracts;

use Modules\Driver\Domain\Entities\Availability;

interface ManageAvailabilityServiceInterface
{
    public function create(array $data): Availability;

    public function update(int $tenantId, string $id, array $data): Availability;

    public function find(int $tenantId, string $id): Availability;

    public function getByDriver(int $tenantId, string $driverId): array;
}
