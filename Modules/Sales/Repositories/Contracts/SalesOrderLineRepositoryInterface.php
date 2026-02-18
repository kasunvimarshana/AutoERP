<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Sales Order Line Repository Interface
 *
 * Defines the contract for sales order line data access operations.
 */
interface SalesOrderLineRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get lines by sales order.
     */
    public function getLinesByOrder(int $salesOrderId): Collection;

    /**
     * Get lines by product.
     */
    public function getLinesByProduct(int $productId): Collection;

    /**
     * Delete lines by sales order.
     */
    public function deleteByOrder(int $salesOrderId): bool;
}
