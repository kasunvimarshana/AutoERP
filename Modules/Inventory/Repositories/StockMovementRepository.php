<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Entities\StockMovement;
use Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;

/**
 * Stock Movement Repository Implementation
 *
 * Handles all stock movement data access operations.
 */
class StockMovementRepository extends BaseRepository implements StockMovementRepositoryInterface
{
    /**
     * StockMovementRepository constructor.
     */
    public function __construct(StockMovement $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getByProduct(int $productId, ?int $limit = null): Collection
    {
        $query = $this->model
            ->where('product_id', $productId)
            ->with(['warehouse', 'stockLocation'])
            ->orderBy('movement_date', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByWarehouse(int $warehouseId, ?int $limit = null): Collection
    {
        $query = $this->model
            ->where('warehouse_id', $warehouseId)
            ->with(['product', 'stockLocation'])
            ->orderBy('movement_date', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByType(string $movementType): Collection
    {
        return $this->model
            ->where('movement_type', $movementType)
            ->orderBy('movement_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByReference(string $referenceType, int $referenceId): Collection
    {
        return $this->model
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->orderBy('movement_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->orderBy('movement_date', 'desc')
            ->get();
    }
}
