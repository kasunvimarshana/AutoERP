<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Procurement\Entities\PurchaseOrder;

/**
 * Purchase Order Repository Interface
 *
 * Defines the contract for purchase order data access operations.
 */
interface PurchaseOrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find purchase order by order number.
     */
    public function findByOrderNumber(string $orderNumber): ?PurchaseOrder;

    /**
     * Get purchase orders by supplier.
     */
    public function getOrdersBySupplier(int $supplierId): Collection;

    /**
     * Get purchase orders by status.
     */
    public function getOrdersByStatus(string $status): Collection;

    /**
     * Get purchase orders by requisition.
     */
    public function getOrdersByRequisition(int $requisitionId): Collection;

    /**
     * Get purchase orders with lines.
     */
    public function findWithLines(int $id): ?PurchaseOrder;

    /**
     * Search purchase orders.
     */
    public function search(string $query): Collection;
}
