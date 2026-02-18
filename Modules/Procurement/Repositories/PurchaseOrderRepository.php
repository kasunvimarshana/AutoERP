<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Procurement\Entities\PurchaseOrder;
use Modules\Procurement\Repositories\Contracts\PurchaseOrderRepositoryInterface;

/**
 * Purchase Order Repository Implementation
 *
 * Handles all purchase order data access operations.
 */
class PurchaseOrderRepository extends BaseRepository implements PurchaseOrderRepositoryInterface
{
    /**
     * PurchaseOrderRepository constructor.
     */
    public function __construct(PurchaseOrder $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByOrderNumber(string $orderNumber): ?PurchaseOrder
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrdersBySupplier(int $supplierId): Collection
    {
        return $this->model->where('supplier_id', $supplierId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrdersByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrdersByRequisition(int $requisitionId): Collection
    {
        return $this->model->where('purchase_requisition_id', $requisitionId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findWithLines(int $id): ?PurchaseOrder
    {
        return $this->model->with('lines')->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('order_number', 'LIKE', "%{$query}%")
            ->orWhereHas('supplier', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->get();
    }
}
