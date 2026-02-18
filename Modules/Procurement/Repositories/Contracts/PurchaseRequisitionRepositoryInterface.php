<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Procurement\Entities\PurchaseRequisition;

/**
 * Purchase Requisition Repository Interface
 *
 * Defines the contract for purchase requisition data access operations.
 */
interface PurchaseRequisitionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find requisition by requisition number.
     */
    public function findByRequisitionNumber(string $requisitionNumber): ?PurchaseRequisition;

    /**
     * Get requisitions by requester.
     */
    public function getRequisitionsByRequester(int $requesterId): Collection;

    /**
     * Get requisitions by status.
     */
    public function getRequisitionsByStatus(string $status): Collection;

    /**
     * Get requisitions by approval status.
     */
    public function getRequisitionsByApprovalStatus(string $approvalStatus): Collection;

    /**
     * Get requisitions with lines.
     */
    public function findWithLines(int $id): ?PurchaseRequisition;

    /**
     * Search requisitions.
     */
    public function search(string $query): Collection;
}
