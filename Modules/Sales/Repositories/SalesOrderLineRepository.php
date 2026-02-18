<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Entities\SalesOrderLine;
use Modules\Sales\Repositories\Contracts\SalesOrderLineRepositoryInterface;

/**
 * Sales Order Line Repository Implementation
 *
 * Handles all sales order line data access operations.
 */
class SalesOrderLineRepository extends BaseRepository implements SalesOrderLineRepositoryInterface
{
    /**
     * SalesOrderLineRepository constructor.
     */
    public function __construct(SalesOrderLine $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getLinesByOrder(int $salesOrderId): Collection
    {
        return $this->model->where('sales_order_id', $salesOrderId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getLinesByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByOrder(int $salesOrderId): bool
    {
        return (bool) $this->model->where('sales_order_id', $salesOrderId)->delete();
    }
}
