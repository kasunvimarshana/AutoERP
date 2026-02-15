<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Repositories;

use App\Core\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseReceiptRepositoryInterface extends RepositoryInterface
{
    /**
     * Find purchase receipt by code
     */
    public function findByCode(string $code): mixed;

    /**
     * Get receipts by purchase order
     */
    public function getByPurchaseOrder(int $purchaseOrderId): Collection;

    /**
     * Get receipts by vendor
     */
    public function getByVendor(int $vendorId): Collection;

    /**
     * Get receipts by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get receipts by quality check status
     */
    public function getByQualityCheckStatus(string $qualityCheckStatus): Collection;

    /**
     * Search receipts
     */
    public function search(string $query, array $filters = []): Collection;
}
