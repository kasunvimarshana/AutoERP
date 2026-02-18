<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Procurement\Entities\PurchaseOrderLine;
use Modules\Procurement\Repositories\Contracts\PurchaseOrderLineRepositoryInterface;

/**
 * Purchase Order Line Repository Implementation
 *
 * Handles all purchase order line data access operations.
 */
class PurchaseOrderLineRepository extends BaseRepository implements PurchaseOrderLineRepositoryInterface
{
    /**
     * PurchaseOrderLineRepository constructor.
     */
    public function __construct(PurchaseOrderLine $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getLinesByOrder(int $orderId): Collection
    {
        return $this->model->where('purchase_order_id', $orderId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByOrder(int $orderId): bool
    {
        return $this->model->where('purchase_order_id', $orderId)->delete();
    }
}
