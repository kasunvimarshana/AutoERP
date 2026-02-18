<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Entities\StockLocation;
use Modules\Inventory\Repositories\Contracts\StockLocationRepositoryInterface;

/**
 * Stock Location Repository Implementation
 *
 * Handles all stock location data access operations.
 */
class StockLocationRepository extends BaseRepository implements StockLocationRepositoryInterface
{
    /**
     * StockLocationRepository constructor.
     */
    public function __construct(StockLocation $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): ?StockLocation
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByWarehouse(int $warehouseId): Collection
    {
        return $this->model
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveLocations(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getLocationsWithCapacity(float $minCapacity = 0): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->whereNotNull('capacity')
            ->get()
            ->filter(function ($location) use ($minCapacity) {
                return $location->getRemainingCapacity() >= $minCapacity;
            });
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('code', 'LIKE', "%{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->get();
    }
}
