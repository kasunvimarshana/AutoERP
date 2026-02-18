<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Procurement\Entities\GoodsReceipt;

/**
 * Goods Receipt Repository Interface
 *
 * Defines the contract for goods receipt data access operations.
 */
interface GoodsReceiptRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find goods receipt by receipt number.
     */
    public function findByReceiptNumber(string $receiptNumber): ?GoodsReceipt;

    /**
     * Get receipts by purchase order.
     */
    public function getReceiptsByPurchaseOrder(int $purchaseOrderId): Collection;

    /**
     * Get receipts by status.
     */
    public function getReceiptsByStatus(string $status): Collection;

    /**
     * Search goods receipts.
     */
    public function search(string $query): Collection;
}
