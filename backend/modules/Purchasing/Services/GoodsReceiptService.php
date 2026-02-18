<?php

declare(strict_types=1);

namespace Modules\Purchasing\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Services\StockService;
use Modules\Purchasing\Enums\GoodsReceiptStatus;
use Modules\Purchasing\Enums\PurchaseOrderStatus;
use Modules\Purchasing\Models\GoodsReceipt;
use Modules\Purchasing\Models\PurchaseOrder;
use Modules\Purchasing\Repositories\GoodsReceiptRepository;
use RuntimeException;

/**
 * Goods Receipt Service
 *
 * Handles business logic for receiving goods from purchase orders.
 * Manages inspection, acceptance, rejection, and inventory integration.
 */
class GoodsReceiptService extends BaseService
{
    protected GoodsReceiptRepository $repository;

    protected StockService $stockService;

    public function __construct(
        GoodsReceiptRepository $repository,
        StockService $stockService
    ) {
        $this->repository = $repository;
        $this->stockService = $stockService;
    }

    /**
     * Create a new goods receipt from a purchase order
     */
    public function createFromPurchaseOrder(int $purchaseOrderId, array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($purchaseOrderId, $data) {
            $purchaseOrder = PurchaseOrder::with('lineItems.product')->findOrFail($purchaseOrderId);

            // Validate purchase order status
            if (! in_array($purchaseOrder->status, [PurchaseOrderStatus::APPROVED, PurchaseOrderStatus::ORDERED])) {
                throw new RuntimeException('Purchase order must be approved or ordered to create goods receipt');
            }

            // Create goods receipt
            $goodsReceipt = $this->repository->create([
                'tenant_id' => $purchaseOrder->tenant_id,
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'warehouse_id' => $data['warehouse_id'],
                'receipt_date' => $data['receipt_date'] ?? now(),
                'status' => GoodsReceiptStatus::DRAFT,
                'received_by' => $data['received_by'] ?? auth()->user()?->name,
                'delivery_note_number' => $data['delivery_note_number'] ?? null,
                'vehicle_number' => $data['vehicle_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create line items
            foreach ($data['line_items'] as $lineItemData) {
                $poLineItem = $purchaseOrder->lineItems->firstWhere('id', $lineItemData['purchase_order_line_item_id']);

                if (! $poLineItem) {
                    throw new RuntimeException('Invalid purchase order line item');
                }

                $goodsReceipt->lineItems()->create([
                    'tenant_id' => $goodsReceipt->tenant_id,
                    'purchase_order_line_item_id' => $poLineItem->id,
                    'product_id' => $poLineItem->product_id,
                    'variant_id' => $poLineItem->variant_id ?? null,
                    'location_id' => $lineItemData['location_id'] ?? null,
                    'ordered_quantity' => $poLineItem->quantity,
                    'received_quantity' => $lineItemData['received_quantity'],
                    'accepted_quantity' => 0,
                    'rejected_quantity' => 0,
                    'unit_of_measure' => $poLineItem->uom ?? 'PCS',
                    'unit_cost' => $poLineItem->unit_price,
                    'batch_number' => $lineItemData['batch_number'] ?? null,
                    'serial_number' => $lineItemData['serial_number'] ?? null,
                    'expiry_date' => $lineItemData['expiry_date'] ?? null,
                ]);
            }

            $this->logEvent('goods_receipt_created', $goodsReceipt);

            return $goodsReceipt->load('lineItems');
        });
    }

    /**
     * Mark goods receipt as received
     */
    public function markAsReceived(int $id): GoodsReceipt
    {
        return DB::transaction(function () use ($id) {
            $goodsReceipt = $this->repository->findOrFail($id);

            if ($goodsReceipt->status !== GoodsReceiptStatus::DRAFT) {
                throw new RuntimeException('Only draft receipts can be marked as received');
            }

            $goodsReceipt->update(['status' => GoodsReceiptStatus::RECEIVED]);

            $this->logEvent('goods_receipt_received', $goodsReceipt);

            return $goodsReceipt;
        });
    }

    /**
     * Inspect goods receipt line items
     */
    public function inspect(int $id, array $inspectionData): GoodsReceipt
    {
        return DB::transaction(function () use ($id, $inspectionData) {
            $goodsReceipt = $this->repository->with('lineItems')->findOrFail($id);

            if ($goodsReceipt->status !== GoodsReceiptStatus::RECEIVED) {
                throw new RuntimeException('Only received goods can be inspected');
            }

            foreach ($inspectionData['line_items'] as $inspectionLineItem) {
                $lineItem = $goodsReceipt->lineItems->firstWhere('id', $inspectionLineItem['line_item_id']);

                if (! $lineItem) {
                    continue;
                }

                $lineItem->update([
                    'accepted_quantity' => $inspectionLineItem['accepted_quantity'] ?? 0,
                    'rejected_quantity' => $inspectionLineItem['rejected_quantity'] ?? 0,
                    'inspection_status' => $inspectionLineItem['inspection_status'],
                    'inspection_notes' => $inspectionLineItem['inspection_notes'] ?? null,
                    'rejection_reason' => $inspectionLineItem['rejection_reason'] ?? null,
                ]);
            }

            $goodsReceipt->update(['status' => GoodsReceiptStatus::INSPECTED]);

            $this->logEvent('goods_receipt_inspected', $goodsReceipt);

            return $goodsReceipt->fresh('lineItems');
        });
    }

    /**
     * Accept goods receipt and update inventory
     */
    public function accept(int $id): GoodsReceipt
    {
        return DB::transaction(function () use ($id) {
            $goodsReceipt = $this->repository->with(['lineItems', 'purchaseOrder'])->findOrFail($id);

            if (! in_array($goodsReceipt->status, [GoodsReceiptStatus::RECEIVED, GoodsReceiptStatus::INSPECTED])) {
                throw new RuntimeException('Only received or inspected goods can be accepted');
            }

            $allAccepted = true;
            $allRejected = true;

            // Process each line item and update inventory
            foreach ($goodsReceipt->lineItems as $lineItem) {
                $acceptedQty = $lineItem->accepted_quantity ?: $lineItem->received_quantity;

                if ($acceptedQty > 0) {
                    // Update inventory
                    $this->stockService->adjust(
                        productId: $lineItem->product_id,
                        warehouseId: $goodsReceipt->warehouse_id,
                        locationId: $lineItem->location_id,
                        quantity: $acceptedQty,
                        type: 'purchase_receipt',
                        reference: "GR-{$goodsReceipt->receipt_number}",
                        notes: "Goods receipt from PO {$goodsReceipt->purchaseOrder->order_number}",
                        unitCost: $lineItem->unit_cost,
                        batchNumber: $lineItem->batch_number,
                        serialNumber: $lineItem->serial_number,
                        expiryDate: $lineItem->expiry_date?->format('Y-m-d'),
                    );

                    // Update line item if not already set
                    if (! $lineItem->accepted_quantity) {
                        $lineItem->update(['accepted_quantity' => $acceptedQty]);
                    }

                    $allRejected = false;
                }

                if ($lineItem->rejected_quantity > 0) {
                    $allAccepted = false;
                }
            }

            // Determine final status
            if ($allAccepted) {
                $goodsReceipt->update(['status' => GoodsReceiptStatus::ACCEPTED]);
            } elseif ($allRejected) {
                $goodsReceipt->update(['status' => GoodsReceiptStatus::REJECTED]);
            } else {
                $goodsReceipt->update(['status' => GoodsReceiptStatus::PARTIALLY_ACCEPTED]);
            }

            // Update purchase order status
            $this->updatePurchaseOrderStatus($goodsReceipt->purchaseOrder);

            $this->logEvent('goods_receipt_accepted', $goodsReceipt);

            return $goodsReceipt->fresh('lineItems');
        });
    }

    /**
     * Reject goods receipt
     */
    public function reject(int $id, string $reason): GoodsReceipt
    {
        return DB::transaction(function () use ($id, $reason) {
            $goodsReceipt = $this->repository->findOrFail($id);

            if (! in_array($goodsReceipt->status, [GoodsReceiptStatus::RECEIVED, GoodsReceiptStatus::INSPECTED])) {
                throw new RuntimeException('Only received or inspected goods can be rejected');
            }

            // Mark all line items as rejected
            foreach ($goodsReceipt->lineItems as $lineItem) {
                $lineItem->update([
                    'rejected_quantity' => $lineItem->received_quantity,
                    'accepted_quantity' => 0,
                    'rejection_reason' => $reason,
                ]);
            }

            $goodsReceipt->update([
                'status' => GoodsReceiptStatus::REJECTED,
                'notes' => ($goodsReceipt->notes ?? '')."\nRejection Reason: {$reason}",
            ]);

            $this->logEvent('goods_receipt_rejected', $goodsReceipt);

            return $goodsReceipt->fresh('lineItems');
        });
    }

    /**
     * Update purchase order status based on receipts
     */
    protected function updatePurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->load('lineItems');

        $totalOrdered = $purchaseOrder->lineItems->sum('quantity');
        $totalReceived = $purchaseOrder->lineItems->sum('quantity_received');

        if ($totalReceived >= $totalOrdered) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::RECEIVED]);
        } elseif ($totalReceived > 0) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::PARTIALLY_RECEIVED]);
        }
    }

    /**
     * Get goods receipts for a purchase order
     */
    public function getByPurchaseOrder(int $purchaseOrderId): array
    {
        return $this->repository
            ->where('purchase_order_id', $purchaseOrderId)
            ->with(['lineItems'])
            ->get()
            ->toArray();
    }

    /**
     * Get all goods receipts with filters
     */
    public function getAll(array $filters = []): array
    {
        $query = $this->repository->newQuery()->with(['purchaseOrder', 'supplier', 'warehouse']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('receipt_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('receipt_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('receipt_date', 'desc')->paginate(20)->toArray();
    }
}
