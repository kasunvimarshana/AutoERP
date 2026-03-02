<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Commands\CreateWarehouseCommand;
use Modules\Inventory\Application\Handlers\CreateWarehouseHandler;
use Modules\Inventory\Domain\Contracts\WarehouseRepositoryInterface;
use Modules\Inventory\Domain\Entities\Warehouse;

/**
 * Service orchestrating all warehouse management operations.
 *
 * Controllers must interact with the warehouse domain exclusively through this
 * service. Read operations are fulfilled directly via the repository contract;
 * write operations are delegated to the appropriate command handlers.
 */
class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly CreateWarehouseHandler $createWarehouseHandler,
    ) {}

    /**
     * Retrieve a paginated list of warehouses for the given tenant.
     *
     * @return array{items: Warehouse[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listWarehouses(int $tenantId, int $page, int $perPage): array
    {
        return $this->warehouseRepository->findAll($tenantId, $page, $perPage);
    }

    /**
     * Find a single warehouse by its identifier within the given tenant.
     */
    public function findWarehouseById(int $warehouseId, int $tenantId): ?Warehouse
    {
        return $this->warehouseRepository->findById($warehouseId, $tenantId);
    }

    /**
     * Create a new warehouse and return the persisted entity.
     */
    public function createWarehouse(CreateWarehouseCommand $command): Warehouse
    {
        return $this->createWarehouseHandler->handle($command);
    }
}
