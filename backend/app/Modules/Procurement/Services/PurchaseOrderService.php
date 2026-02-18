<?php

namespace App\Modules\Procurement\Services;

use App\Enums\StockMovementType;
use App\Events\PurchaseOrderApproved;
use App\Events\PurchaseOrderCreated;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Procurement\DTOs\PurchaseOrderDTO;
use App\Modules\Procurement\DTOs\PurchaseOrderReceiptDTO;
use App\Modules\Procurement\Enums\PurchaseOrderStatus;
use App\Modules\Procurement\Models\PurchaseOrder;
use App\Modules\Procurement\Repositories\PurchaseOrderRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;

/**
 * Purchase Order Service
 *
 * Handles business logic for purchase order management including
 * creation, approval, receiving, and cancellation.
 */
class PurchaseOrderService extends BaseService
{
    public function __construct(
        protected PurchaseOrderRepository $purchaseOrderRepository,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Get all purchase orders
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllPurchaseOrders(int $perPage = 15)
    {
        return $this->purchaseOrderRepository->paginate($perPage);
    }

    /**
     * Create a new purchase order
     *
     * @throws \Throwable
     */
    public function createPurchaseOrder(PurchaseOrderDTO $dto): PurchaseOrder
    {
        return $this->transaction(function () use ($dto) {
            $this->logInfo('Creating new purchase order', ['po_number' => $dto->poNumber]);

            // Create purchase order
            $purchaseOrder = $this->purchaseOrderRepository->create([
                'supplier_id' => $dto->supplierId,
                'warehouse_id' => $dto->warehouseId,
                'po_number' => $dto->poNumber,
                'po_date' => $dto->poDate,
                'expected_delivery_date' => $dto->expectedDeliveryDate,
                'status' => $dto->status?->value ?? PurchaseOrderStatus::DRAFT->value,
                'subtotal' => $dto->subtotal,
                'discount_amount' => $dto->discountAmount,
                'tax_amount' => $dto->taxAmount,
                'total_amount' => $dto->totalAmount,
                'notes' => $dto->notes,
            ]);

            // Create purchase order items
            if ($dto->items) {
                foreach ($dto->items as $item) {
                    $purchaseOrder->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'tax_amount' => $item['tax_amount'] ?? 0,
                        'total_amount' => $item['total_amount'],
                        'received_quantity' => 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Load relationships
            $purchaseOrder->load(['supplier', 'items.product']);

            // Dispatch event
            event(new PurchaseOrderCreated($purchaseOrder));

            $this->logInfo('Purchase order created successfully', ['id' => $purchaseOrder->id]);

            return $purchaseOrder;
        });
    }

    /**
     * Update a purchase order
     *
     * @throws \Throwable
     */
    public function updatePurchaseOrder(int $id, PurchaseOrderDTO $dto): PurchaseOrder
    {
        return $this->transaction(function () use ($id, $dto) {
            $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

            // Check if PO can be edited
            if (! $purchaseOrder->status->canEdit()) {
                throw new \InvalidArgumentException(
                    "Purchase order with status '{$purchaseOrder->status->label()}' cannot be edited"
                );
            }

            $this->logInfo('Updating purchase order', ['id' => $id]);

            // Update purchase order
            $this->purchaseOrderRepository->update($id, [
                'supplier_id' => $dto->supplierId,
                'warehouse_id' => $dto->warehouseId,
                'po_number' => $dto->poNumber,
                'po_date' => $dto->poDate,
                'expected_delivery_date' => $dto->expectedDeliveryDate,
                'subtotal' => $dto->subtotal,
                'discount_amount' => $dto->discountAmount,
                'tax_amount' => $dto->taxAmount,
                'total_amount' => $dto->totalAmount,
                'notes' => $dto->notes,
            ]);

            // Update items if provided
            if ($dto->items) {
                // Delete existing items
                $purchaseOrder->items()->delete();

                // Create new items
                foreach ($dto->items as $item) {
                    $purchaseOrder->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'tax_amount' => $item['tax_amount'] ?? 0,
                        'total_amount' => $item['total_amount'],
                        'received_quantity' => 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            $purchaseOrder->refresh();
            $purchaseOrder->load(['supplier', 'items.product']);

            $this->logInfo('Purchase order updated successfully', ['id' => $id]);

            return $purchaseOrder;
        });
    }

    /**
     * Delete a purchase order
     */
    public function deletePurchaseOrder(int $id): bool
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        // Check if PO can be edited
        if (! $purchaseOrder->status->canEdit()) {
            throw new \InvalidArgumentException(
                "Purchase order with status '{$purchaseOrder->status->label()}' cannot be deleted"
            );
        }

        $this->logInfo('Deleting purchase order', ['id' => $id]);

        $result = $this->purchaseOrderRepository->delete($id);

        if ($result) {
            $this->logInfo('Purchase order deleted successfully', ['id' => $id]);
        }

        return $result;
    }

    /**
     * Get a purchase order by ID
     */
    public function getPurchaseOrderById(int $id): PurchaseOrder
    {
        return $this->purchaseOrderRepository->findWithItems($id);
    }

    /**
     * Approve a purchase order
     *
     * @throws \Throwable
     */
    public function approve(int $id): PurchaseOrder
    {
        return $this->transaction(function () use ($id) {
            $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

            // Check if PO can be approved
            if (! $purchaseOrder->status->canApprove()) {
                throw new \InvalidArgumentException(
                    "Purchase order with status '{$purchaseOrder->status->label()}' cannot be approved"
                );
            }

            $this->logInfo('Approving purchase order', ['id' => $id]);

            // Update status and approval details
            $this->purchaseOrderRepository->update($id, [
                'status' => PurchaseOrderStatus::APPROVED->value,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $purchaseOrder->refresh();
            $purchaseOrder->load(['supplier', 'items.product']);

            // Dispatch event
            event(new PurchaseOrderApproved($purchaseOrder));

            $this->logInfo('Purchase order approved successfully', ['id' => $id]);

            return $purchaseOrder;
        });
    }

    /**
     * Receive goods from a purchase order
     *
     * @throws \Throwable
     */
    public function receive(int $id, PurchaseOrderReceiptDTO $dto): PurchaseOrder
    {
        return $this->transaction(function () use ($id, $dto) {
            $purchaseOrder = $this->purchaseOrderRepository->findWithItems($id);

            // Check if PO can receive goods
            if (! $purchaseOrder->status->canReceive()) {
                throw new \InvalidArgumentException(
                    "Purchase order with status '{$purchaseOrder->status->label()}' cannot receive goods"
                );
            }

            $this->logInfo('Receiving goods for purchase order', ['id' => $id]);

            $allItemsFullyReceived = true;

            // Process each received item
            foreach ($dto->receivedItems as $receivedItem) {
                $poItem = $purchaseOrder->items()
                    ->where('id', $receivedItem['purchase_order_item_id'])
                    ->first();

                if (! $poItem) {
                    throw new \InvalidArgumentException(
                        "Purchase order item {$receivedItem['purchase_order_item_id']} not found"
                    );
                }

                $receivedQty = $receivedItem['received_quantity'];

                // Validate received quantity
                if ($receivedQty <= 0) {
                    throw new \InvalidArgumentException('Received quantity must be greater than 0');
                }

                if (($poItem->received_quantity + $receivedQty) > $poItem->quantity) {
                    throw new \InvalidArgumentException(
                        "Received quantity exceeds ordered quantity for item {$poItem->id}"
                    );
                }

                // Update received quantity
                $poItem->received_quantity += $receivedQty;
                $poItem->save();

                // Record stock movement
                $stockMovementDto = new StockMovementDTO(
                    productId: $poItem->product_id,
                    movementType: StockMovementType::PURCHASE,
                    quantity: $receivedQty,
                    unitCost: $poItem->unit_price,
                    warehouseId: $purchaseOrder->warehouse_id,
                    referenceType: PurchaseOrder::class,
                    referenceId: $purchaseOrder->id,
                    notes: "Received from PO #{$purchaseOrder->po_number}",
                    batchNumber: $receivedItem['batch_number'] ?? null,
                    lotNumber: $receivedItem['lot_number'] ?? null,
                    serialNumber: $receivedItem['serial_number'] ?? null,
                    expiryDate: $receivedItem['expiry_date'] ?? null,
                );

                $this->inventoryService->recordStockMovement($stockMovementDto);

                // Check if item is fully received
                if (! $poItem->isFullyReceived()) {
                    $allItemsFullyReceived = false;
                }
            }

            // Update purchase order status
            $newStatus = $allItemsFullyReceived
                ? PurchaseOrderStatus::RECEIVED
                : PurchaseOrderStatus::PARTIALLY_RECEIVED;

            $updateData = ['status' => $newStatus->value];

            // If fully received, update received_by and received_at
            if ($allItemsFullyReceived) {
                $updateData['received_by'] = $dto->receivedBy ?? Auth::id();
                $updateData['received_at'] = $dto->receivedAt ?? now();
            }

            $this->purchaseOrderRepository->update($id, $updateData);

            $purchaseOrder->refresh();
            $purchaseOrder->load(['supplier', 'items.product']);

            $this->logInfo('Goods received successfully', [
                'id' => $id,
                'status' => $newStatus->value,
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Cancel a purchase order
     *
     * @throws \Throwable
     */
    public function cancel(int $id): PurchaseOrder
    {
        return $this->transaction(function () use ($id) {
            $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

            // Check if PO can be cancelled
            if ($purchaseOrder->status === PurchaseOrderStatus::CANCELLED) {
                throw new \InvalidArgumentException('Purchase order is already cancelled');
            }

            if ($purchaseOrder->status === PurchaseOrderStatus::RECEIVED) {
                throw new \InvalidArgumentException('Cannot cancel a fully received purchase order');
            }

            $this->logInfo('Cancelling purchase order', ['id' => $id]);

            $this->purchaseOrderRepository->update($id, [
                'status' => PurchaseOrderStatus::CANCELLED->value,
            ]);

            $purchaseOrder->refresh();
            $purchaseOrder->load(['supplier', 'items.product']);

            $this->logInfo('Purchase order cancelled successfully', ['id' => $id]);

            return $purchaseOrder;
        });
    }

    /**
     * Search purchase orders
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchPurchaseOrders(string $search)
    {
        return $this->purchaseOrderRepository->search($search);
    }

    /**
     * Get purchase orders by status
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(PurchaseOrderStatus $status)
    {
        return $this->purchaseOrderRepository->getByStatus($status);
    }

    /**
     * Get pending purchase orders
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingPurchaseOrders()
    {
        return $this->purchaseOrderRepository->getPending();
    }

    /**
     * Get purchase orders needing approval
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNeedingApproval()
    {
        return $this->purchaseOrderRepository->getNeedingApproval();
    }
}
