<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Models\Warehouse;

/**
 * Warehouse Repository
 *
 * Handles data access for warehouses.
 */
class WarehouseRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Warehouse::class;
    }

    /**
     * Find warehouse by code.
     */
    public function findByCode(string $code): ?Warehouse
    {
        return $this->newQuery()->where('code', $code)->first();
    }

    /**
     * Get active warehouses.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveWarehouses()
    {
        return $this->newQuery()->active()->get();
    }
}
