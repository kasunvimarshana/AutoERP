<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Procurement\Entities\GoodsReceipt;
use Modules\Procurement\Repositories\Contracts\GoodsReceiptRepositoryInterface;

/**
 * Goods Receipt Repository Implementation
 *
 * Handles all goods receipt data access operations.
 */
class GoodsReceiptRepository extends BaseRepository implements GoodsReceiptRepositoryInterface
{
    /**
     * GoodsReceiptRepository constructor.
     */
    public function __construct(GoodsReceipt $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByReceiptNumber(string $receiptNumber): ?GoodsReceipt
    {
        return $this->model->where('receipt_number', $receiptNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getReceiptsByPurchaseOrder(int $purchaseOrderId): Collection
    {
        return $this->model->where('purchase_order_id', $purchaseOrderId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getReceiptsByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('receipt_number', 'LIKE', "%{$query}%")
            ->orWhereHas('purchaseOrder', function ($q) use ($query) {
                $q->where('order_number', 'LIKE', "%{$query}%");
            })
            ->get();
    }
}
