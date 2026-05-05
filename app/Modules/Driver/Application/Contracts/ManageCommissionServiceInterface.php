<?php declare(strict_types=1);

namespace Modules\Driver\Application\Contracts;

use Modules\Driver\Domain\Entities\Commission;

interface ManageCommissionServiceInterface
{
    public function getByDriver(int $tenantId, string $driverId): array;

    public function getPending(int $tenantId): array;
}
