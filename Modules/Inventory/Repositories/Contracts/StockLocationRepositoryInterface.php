<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Inventory\Entities\StockLocation;

/**
 * Stock Location Repository Interface
 *
 * Defines the contract for stock location data access operations.
 */
interface StockLocationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find location by code.
     */
    public function findByCode(string $code): ?StockLocation;

    /**
     * Get locations by warehouse.
     */
    public function getByWarehouse(int $warehouseId): Collection;

    /**
     * Get active locations.
     */
    public function getActiveLocations(): Collection;

    /**
     * Get locations with available capacity.
     */
    public function getLocationsWithCapacity(float $minCapacity = 0): Collection;

    /**
     * Search locations by code or name.
     */
    public function search(string $query): Collection;
}
