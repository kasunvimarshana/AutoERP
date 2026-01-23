<?php

namespace App\Modules\InventoryManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InventoryManagement\Repositories\PurchaseOrderItemRepository;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItemService extends BaseService
{
    public function __construct(PurchaseOrderItemRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get items by purchase order
     */
    public function getByPurchaseOrder(int $purchaseOrderId)
    {
        return $this->repository->getByPurchaseOrder($purchaseOrderId);
    }

    /**
     * Update received quantity
     */
    public function updateReceivedQuantity(int $itemId, int $quantity): Model
    {
        $item = $this->repository->findOrFail($itemId);
        $item->received_quantity += $quantity;
        $item->save();

        return $item;
    }

    /**
     * Mark item as fully received
     */
    public function markAsReceived(int $itemId): Model
    {
        return $this->update($itemId, ['status' => 'received']);
    }

    /**
     * Calculate line total
     */
    public function calculateLineTotal(int $itemId): float
    {
        $item = $this->repository->findOrFail($itemId);
        return $item->quantity * $item->unit_price;
    }

    /**
     * Update unit price
     */
    public function updateUnitPrice(int $itemId, float $unitPrice): Model
    {
        return $this->update($itemId, ['unit_price' => $unitPrice]);
    }

    /**
     * Update quantity
     */
    public function updateQuantity(int $itemId, int $quantity): Model
    {
        return $this->update($itemId, ['quantity' => $quantity]);
    }
}
