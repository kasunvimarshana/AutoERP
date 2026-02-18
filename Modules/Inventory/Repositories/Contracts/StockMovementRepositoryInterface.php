<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Stock Movement Repository Interface
 *
 * Defines the contract for stock movement data access operations.
 */
interface StockMovementRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get movements by product.
     */
    public function getByProduct(int $productId, ?int $limit = null): Collection;

    /**
     * Get movements by warehouse.
     */
    public function getByWarehouse(int $warehouseId, ?int $limit = null): Collection;

    /**
     * Get movements by type.
     */
    public function getByType(string $movementType): Collection;

    /**
     * Get movements by reference.
     */
    public function getByReference(string $referenceType, int $referenceId): Collection;

    /**
     * Get movements in date range.
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;
}
