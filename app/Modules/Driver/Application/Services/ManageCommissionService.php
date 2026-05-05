<?php declare(strict_types=1);

namespace Modules\Driver\Application\Services;

use Modules\Driver\Application\Contracts\ManageCommissionServiceInterface;
use Modules\Driver\Domain\Entities\Commission;
use Modules\Driver\Domain\RepositoryInterfaces\DriverCommissionRepositoryInterface;

class ManageCommissionService implements ManageCommissionServiceInterface
{
    public function __construct(
        private readonly DriverCommissionRepositoryInterface $commissions,
    ) {}

    public function getByDriver(int $tenantId, string $driverId): array
    {
        return $this->commissions->getByDriver((string) $tenantId, $driverId);
    }

    public function getPending(int $tenantId): array
    {
        return $this->commissions->getPending((string) $tenantId);
    }
}
