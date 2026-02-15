<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Repositories;

use App\Core\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseOrderRepositoryInterface extends RepositoryInterface
{
    /**
     * Find purchase order by code
     */
    public function findByCode(string $code): mixed;

    /**
     * Get purchase orders by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get purchase orders by approval status
     */
    public function getByApprovalStatus(string $approvalStatus): Collection;

    /**
     * Get purchase orders by payment status
     */
    public function getByPaymentStatus(string $paymentStatus): Collection;

    /**
     * Get purchase orders by vendor
     */
    public function getByVendor(int $vendorId): Collection;

    /**
     * Get pending purchase orders
     */
    public function getPendingOrders(): Collection;

    /**
     * Get overdue purchase orders
     */
    public function getOverdueOrders(): Collection;

    /**
     * Search purchase orders
     */
    public function search(string $query, array $filters = []): Collection;
}
