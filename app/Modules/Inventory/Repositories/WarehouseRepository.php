<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

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
     * Get active warehouses
     */
    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get warehouses by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Get warehouses by location
     */
    public function getByLocation(string $location): Collection
    {
        return $this->model->where('location', 'like', "%{$location}%")->get();
    }
}
