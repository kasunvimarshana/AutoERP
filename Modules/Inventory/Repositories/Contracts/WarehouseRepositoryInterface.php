<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Inventory\Entities\Warehouse;

/**
 * Warehouse Repository Interface
 *
 * Defines the contract for warehouse data access operations.
 */
interface WarehouseRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find warehouse by code.
     */
    public function findByCode(string $code): ?Warehouse;

    /**
     * Get active warehouses.
     */
    public function getActiveWarehouses(): Collection;

    /**
     * Search warehouses by name or code.
     */
    public function search(string $query): Collection;

    /**
     * Get warehouses by type.
     */
    public function getByType(string $type): Collection;
}
