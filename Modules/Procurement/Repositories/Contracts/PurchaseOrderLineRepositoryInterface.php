<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Purchase Order Line Repository Interface
 *
 * Defines the contract for purchase order line data access operations.
 */
interface PurchaseOrderLineRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get lines by purchase order.
     */
    public function getLinesByOrder(int $orderId): Collection;

    /**
     * Delete lines by purchase order.
     */
    public function deleteByOrder(int $orderId): bool;
}
