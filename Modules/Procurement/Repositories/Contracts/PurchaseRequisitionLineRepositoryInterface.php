<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Purchase Requisition Line Repository Interface
 *
 * Defines the contract for purchase requisition line data access operations.
 */
interface PurchaseRequisitionLineRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get lines by requisition.
     */
    public function getLinesByRequisition(int $requisitionId): Collection;

    /**
     * Delete lines by requisition.
     */
    public function deleteByRequisition(int $requisitionId): bool;
}
