<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Entities\Warehouse;
use Modules\Inventory\Repositories\Contracts\WarehouseRepositoryInterface;

/**
 * Warehouse Repository Implementation
 *
 * Handles all warehouse data access operations.
 */
class WarehouseRepository extends BaseRepository implements WarehouseRepositoryInterface
{
    /**
     * WarehouseRepository constructor.
     */
    public function __construct(Warehouse $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByCode(string $code): ?Warehouse
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveWarehouses(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('code', 'LIKE', "%{$query}%")
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByType(string $type): Collection
    {
        return $this->model
            ->where('warehouse_type', $type)
            ->where('is_active', true)
            ->get();
    }
}
