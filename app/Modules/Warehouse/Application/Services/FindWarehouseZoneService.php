<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\FindWarehouseZoneServiceInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;

/**
 * Read-only service for querying warehouse zones.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindWarehouseZoneService extends BaseService implements FindWarehouseZoneServiceInterface
{
    public function __construct(private readonly WarehouseZoneRepositoryInterface $zoneRepository)
    {
        parent::__construct($zoneRepository);
    }

    /**
     * Return all zones belonging to a given warehouse.
     *
     * @return array<int, \Modules\Warehouse\Domain\Entities\WarehouseZone>
     */
    public function getByWarehouse(int $warehouseId): array
    {
        return $this->zoneRepository->getByWarehouse($warehouseId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
