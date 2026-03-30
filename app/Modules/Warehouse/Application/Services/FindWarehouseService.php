<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\FindWarehouseServiceInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

/**
 * Read-only service for querying warehouses.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindWarehouseService extends BaseService implements FindWarehouseServiceInterface
{
    public function __construct(private readonly WarehouseRepositoryInterface $warehouseRepository)
    {
        parent::__construct($warehouseRepository);
    }

    /**
     * Return all warehouses associated with a given location.
     *
     * @return array<int, \Modules\Warehouse\Domain\Entities\Warehouse>
     */
    public function getByLocation(int $locationId): array
    {
        return $this->warehouseRepository->getByLocation($locationId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
