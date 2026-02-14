<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

/**
 * Warehouse Repository
 * 
 * Handles data access operations for warehouses
 */
class WarehouseRepository extends BaseRepository
{
    /**
     * Specify the model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return Warehouse::class;
    }

    /**
     * Find warehouse by code
     *
     * @param string $code
     * @return Warehouse|null
     */
    public function findByCode(string $code): ?Warehouse
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get active warehouses
     *
     * @return Collection
     */
    public function getActiveWarehouses(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get warehouses by type
     *
     * @param string $type
     * @return Collection
     */
    public function findByType(string $type): Collection
    {
        return $this->model
            ->where('type', $type)
            ->where('is_active', true)
            ->get();
    }
}
