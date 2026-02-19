<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Helpers\MathHelper;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Purchase\Enums\GoodsReceiptStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Events\GoodsReceiptCreated;
use Modules\Purchase\Events\GoodsReceiptPosted;
use Modules\Purchase\Exceptions\InvalidPurchaseOrderStatusException;
use Modules\Purchase\Models\GoodsReceipt;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Repositories\GoodsReceiptRepository;
use Modules\Purchase\Repositories\PurchaseOrderRepository;

/**
 * Goods Receipt Service
 *
 * Handles business logic for goods receipts including creation,
 * confirmation, and inventory posting.
 */
class GoodsReceiptService
{
    public function __construct(
        private GoodsReceiptRepository $goodsReceiptRepository,
        private PurchaseOrderRepository $purchaseOrderRepository,
        private CodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create a goods receipt from a purchase order.
     */
    public function create(string $poId, array $data, array $items = []): GoodsReceipt
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($poId);

        if (! $purchaseOrder->status->canReceiveGoods()) {
            throw new InvalidPurchaseOrderStatusException(
                "Cannot create goods receipt for purchase order in {$purchaseOrder->status->value} status"
            );
        }

        return TransactionHelper::execute(function () use ($purchaseOrder, $data, $items) {
            // Generate GR code if not provided
            if (empty($data['receipt_code'])) {
                $data['receipt_code'] = $this->generateGrCode();
            }

            // Set required fields
            $data['purchase_order_id'] = $purchaseOrder->id;
            $data['vendor_id'] = $purchaseOrder->vendor_id;
            $data['tenant_id'] = $data['tenant_id'] ?? $purchaseOrder->tenant_id;
            $data['organization_id'] = $data['organization_id'] ?? $purchaseOrder->organization_id;
            $data['status'] = $data['status'] ?? GoodsReceiptStatus::DRAFT;
            $data['receipt_date'] = $data['receipt_date'] ?? now();

            // Create goods receipt
            $goodsReceipt = $this->goodsReceiptRepository->create($data);

            // Create items if provided
            if (! empty($items)) {
                foreach ($items as $item) {
                    $item['goods_receipt_id'] = $goodsReceipt->id;
                    $goodsReceipt->items()->create($item);
                }
                $goodsReceipt->load('items');
            }

            // Fire event
            event(new GoodsReceiptCreated($goodsReceipt));

            return $goodsReceipt;
        });
    }

    /**
     * Update goods receipt.
     */
    public function update(string $id, array $data): GoodsReceipt
    {
        $goodsReceipt = $this->goodsReceiptRepository->findOrFail($id);

        return $this->goodsReceiptRepository->update($goodsReceipt->id, $data);
    }

    /**
     * Delete goods receipt.
     */
    public function delete(string $id): bool
    {
        $goodsReceipt = $this->goodsReceiptRepository->findOrFail($id);

        return $this->goodsReceiptRepository->delete($goodsReceipt->id);
    }

    /**
     * Confirm goods receipt.
     */
    public function confirm(string $id): GoodsReceipt
    {
        $goodsReceipt = $this->goodsReceiptRepository->findOrFail($id);

        if ($goodsReceipt->status !== GoodsReceiptStatus::DRAFT) {
            throw new InvalidPurchaseOrderStatusException(
                "Goods receipt cannot be confirmed in {$goodsReceipt->status->value} status"
            );
        }

        return TransactionHelper::execute(function () use ($goodsReceipt) {
            $goodsReceipt = $this->goodsReceiptRepository->update($goodsReceipt->id, [
                'status' => GoodsReceiptStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);

            // Update purchase order receipt status
            $this->updatePurchaseOrderReceiptStatus($goodsReceipt->purchaseOrder);

            return $goodsReceipt;
        });
    }

    /**
     * Post goods receipt to inventory.
     */
    public function postToInventory(string $id): GoodsReceipt
    {
        $goodsReceipt = $this->goodsReceiptRepository->findOrFail($id);

        if ($goodsReceipt->status !== GoodsReceiptStatus::CONFIRMED) {
            throw new InvalidPurchaseOrderStatusException(
                'Only confirmed goods receipts can be posted to inventory'
            );
        }

        if ($goodsReceipt->posted_at !== null) {
            throw new InvalidPurchaseOrderStatusException(
                'Goods receipt has already been posted to inventory'
            );
        }

        return TransactionHelper::execute(function () use ($goodsReceipt) {
            $goodsReceipt = $this->goodsReceiptRepository->update($goodsReceipt->id, [
                'status' => GoodsReceiptStatus::POSTED,
                'posted_at' => now(),
            ]);

            // Fire event for inventory integration
            event(new GoodsReceiptPosted($goodsReceipt));

            // Update purchase order receipt status
            $this->updatePurchaseOrderReceiptStatus($goodsReceipt->purchaseOrder);

            return $goodsReceipt;
        });
    }

    /**
     * Cancel goods receipt.
     */
    public function cancel(string $id, ?string $reason = null): GoodsReceipt
    {
        $goodsReceipt = $this->goodsReceiptRepository->findOrFail($id);

        if ($goodsReceipt->status === GoodsReceiptStatus::POSTED) {
            throw new InvalidPurchaseOrderStatusException(
                'Cannot cancel goods receipt that has been posted to inventory'
            );
        }

        if ($goodsReceipt->status === GoodsReceiptStatus::CANCELLED) {
            throw new InvalidPurchaseOrderStatusException(
                'Goods receipt is already cancelled'
            );
        }

        return TransactionHelper::execute(function () use ($goodsReceipt, $reason) {
            $goodsReceipt = $this->goodsReceiptRepository->update($goodsReceipt->id, [
                'status' => GoodsReceiptStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Update purchase order receipt status
            $this->updatePurchaseOrderReceiptStatus($goodsReceipt->purchaseOrder);

            return $goodsReceipt;
        });
    }

    /**
     * Update purchase order receipt status based on goods receipts.
     */
    public function updatePurchaseOrderReceiptStatus(PurchaseOrder $po): void
    {
        // Get all posted goods receipts for this PO
        $goodsReceipts = $po->goodsReceipts()
            ->where('status', GoodsReceiptStatus::POSTED)
            ->with('items')
            ->get();

        if ($goodsReceipts->isEmpty()) {
            return;
        }

        // Calculate total received quantities per product
        $receivedQuantities = [];
        foreach ($goodsReceipts as $gr) {
            foreach ($gr->items as $item) {
                $productId = $item->product_id;
                if (! isset($receivedQuantities[$productId])) {
                    $receivedQuantities[$productId] = '0';
                }
                $receivedQuantities[$productId] = MathHelper::add(
                    $receivedQuantities[$productId],
                    (string) $item->quantity_received
                );
            }
        }

        // Check if fully or partially received
        $fullyReceived = true;
        $partiallyReceived = false;

        foreach ($po->items as $poItem) {
            $productId = $poItem->product_id;
            $orderedQty = (string) $poItem->quantity;
            $receivedQty = $receivedQuantities[$productId] ?? '0';

            $comparison = MathHelper::compare($receivedQty, $orderedQty);

            if ($comparison < 0) {
                $fullyReceived = false;
                if (MathHelper::compare($receivedQty, '0') > 0) {
                    $partiallyReceived = true;
                }
            } elseif ($comparison > 0) {
                $partiallyReceived = true;
            }
        }

        // Update purchase order status
        $updateData = [];

        if ($fullyReceived) {
            $updateData['status'] = PurchaseOrderStatus::RECEIVED;
            $updateData['received_at'] = now();
        } elseif ($partiallyReceived) {
            $updateData['status'] = PurchaseOrderStatus::PARTIALLY_RECEIVED;
        }

        if (! empty($updateData)) {
            $this->purchaseOrderRepository->update($po->id, $updateData);
        }
    }

    /**
     * Generate unique GR code.
     */
    private function generateGrCode(): string
    {
        $prefix = config('purchase.gr.code_prefix', 'GR-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->goodsReceiptRepository->findByGrCode($code) !== null
        );
    }
}
