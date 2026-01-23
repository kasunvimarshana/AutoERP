<?php

namespace App\Modules\InventoryManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InventoryManagement\Models\PurchaseOrderItem;

class PurchaseOrderItemRepository extends BaseRepository
{
    public function __construct(PurchaseOrderItem $model)
    {
        parent::__construct($model);
    }

    /**
     * Search purchase order items by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('inventoryItem', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($criteria['purchase_order_id'])) {
            $query->where('purchase_order_id', $criteria['purchase_order_id']);
        }

        if (!empty($criteria['inventory_item_id'])) {
            $query->where('inventory_item_id', $criteria['inventory_item_id']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['purchaseOrder', 'inventoryItem'])
            ->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get items for purchase order
     */
    public function getForPurchaseOrder(int $purchaseOrderId)
    {
        return $this->model->where('purchase_order_id', $purchaseOrderId)
            ->with(['inventoryItem'])
            ->get();
    }

    /**
     * Get items for inventory item
     */
    public function getForInventoryItem(int $inventoryItemId)
    {
        return $this->model->where('inventory_item_id', $inventoryItemId)
            ->with(['purchaseOrder'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get pending items
     */
    public function getPending()
    {
        return $this->model->whereHas('purchaseOrder', function ($query) {
            $query->whereIn('status', ['pending', 'approved']);
        })->with(['purchaseOrder', 'inventoryItem'])->get();
    }

    /**
     * Get received items
     */
    public function getReceived()
    {
        return $this->model->whereHas('purchaseOrder', function ($query) {
            $query->where('status', 'received');
        })->with(['purchaseOrder', 'inventoryItem'])->get();
    }
}
