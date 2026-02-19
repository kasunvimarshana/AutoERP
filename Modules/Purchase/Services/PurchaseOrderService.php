<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Core\Services\TotalCalculationService;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Events\PurchaseOrderApproved;
use Modules\Purchase\Events\PurchaseOrderCancelled;
use Modules\Purchase\Events\PurchaseOrderConfirmed;
use Modules\Purchase\Events\PurchaseOrderCreated;
use Modules\Purchase\Events\PurchaseOrderSent;
use Modules\Purchase\Exceptions\InvalidPurchaseOrderStatusException;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Repositories\PurchaseOrderRepository;
use Modules\Purchase\Repositories\VendorRepository;

/**
 * Purchase Order Service
 *
 * Handles business logic for purchase orders including lifecycle management,
 * status transitions, and approval workflows.
 */
class PurchaseOrderService
{
    public function __construct(
        private PurchaseOrderRepository $purchaseOrderRepository,
        private VendorRepository $vendorRepository,
        private CodeGeneratorService $codeGenerator,
        private TotalCalculationService $totalCalculationService
    ) {}

    /**
     * Create a new purchase order with items.
     */
    public function create(array $data, array $items = []): PurchaseOrder
    {
        return TransactionHelper::execute(function () use ($data, $items) {
            // Generate PO code if not provided
            if (empty($data['po_code'])) {
                $data['po_code'] = $this->generatePoCode();
            }

            // Set default status and dates
            $data['status'] = $data['status'] ?? PurchaseOrderStatus::DRAFT;
            $data['order_date'] = $data['order_date'] ?? now();

            // Validate vendor exists and can receive orders
            $vendor = $this->vendorRepository->findOrFail($data['vendor_id']);
            if (! $vendor->canReceiveOrders()) {
                throw new InvalidPurchaseOrderStatusException(
                    "Vendor {$vendor->name} cannot receive orders"
                );
            }

            // Calculate totals if items are provided
            if (! empty($items)) {
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Create purchase order
            $purchaseOrder = $this->purchaseOrderRepository->create($data);

            // Create items if provided
            if (! empty($items)) {
                foreach ($items as $item) {
                    $item['purchase_order_id'] = $purchaseOrder->id;
                    $purchaseOrder->items()->create($item);
                }
                $purchaseOrder->load('items');
            }

            // Fire event
            event(new PurchaseOrderCreated($purchaseOrder));

            return $purchaseOrder;
        });
    }

    /**
     * Update purchase order and recalculate totals.
     */
    public function update(string $id, array $data, ?array $items = null): PurchaseOrder
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        if (! $purchaseOrder->status->isEditable()) {
            throw new InvalidPurchaseOrderStatusException(
                "Purchase order cannot be modified in {$purchaseOrder->status->value} status"
            );
        }

        return TransactionHelper::execute(function () use ($purchaseOrder, $data, $items) {
            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $purchaseOrder->items()->delete();

                // Create new items
                foreach ($items as $item) {
                    $item['purchase_order_id'] = $purchaseOrder->id;
                    $purchaseOrder->items()->create($item);
                }

                // Recalculate totals
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Update purchase order
            return $this->purchaseOrderRepository->update($purchaseOrder->id, $data);
        });
    }

    /**
     * Approve purchase order.
     */
    public function approve(string $id, string $approvedBy): PurchaseOrder
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        if ($purchaseOrder->status !== PurchaseOrderStatus::PENDING) {
            throw new InvalidPurchaseOrderStatusException(
                "Purchase order cannot be approved in {$purchaseOrder->status->value} status"
            );
        }

        $purchaseOrder = $this->purchaseOrderRepository->update($purchaseOrder->id, [
            'status' => PurchaseOrderStatus::APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        // Fire event
        event(new PurchaseOrderApproved($purchaseOrder));

        return $purchaseOrder;
    }

    /**
     * Send purchase order to vendor.
     */
    public function send(string $id): PurchaseOrder
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        if ($purchaseOrder->status !== PurchaseOrderStatus::APPROVED) {
            throw new InvalidPurchaseOrderStatusException(
                'Purchase order must be approved before sending'
            );
        }

        $purchaseOrder = $this->purchaseOrderRepository->update($purchaseOrder->id, [
            'status' => PurchaseOrderStatus::SENT,
            'sent_at' => now(),
        ]);

        // Fire event
        event(new PurchaseOrderSent($purchaseOrder));

        return $purchaseOrder;
    }

    /**
     * Confirm purchase order (vendor acknowledgment).
     */
    public function confirm(string $id): PurchaseOrder
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        if ($purchaseOrder->status !== PurchaseOrderStatus::SENT) {
            throw new InvalidPurchaseOrderStatusException(
                'Purchase order must be sent before confirmation'
            );
        }

        $purchaseOrder = $this->purchaseOrderRepository->update($purchaseOrder->id, [
            'status' => PurchaseOrderStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);

        // Fire event
        event(new PurchaseOrderConfirmed($purchaseOrder));

        return $purchaseOrder;
    }

    /**
     * Cancel purchase order.
     */
    public function cancel(string $id, ?string $reason = null): PurchaseOrder
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        if (! $purchaseOrder->status->isCancellable()) {
            throw new InvalidPurchaseOrderStatusException(
                "Purchase order cannot be cancelled in {$purchaseOrder->status->value} status"
            );
        }

        $purchaseOrder = $this->purchaseOrderRepository->update($purchaseOrder->id, [
            'status' => PurchaseOrderStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Fire event
        event(new PurchaseOrderCancelled($purchaseOrder));

        return $purchaseOrder;
    }

    /**
     * Delete purchase order.
     */
    public function delete(string $id): bool
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        return $this->purchaseOrderRepository->delete($purchaseOrder->id);
    }

    /**
     * Calculate totals for purchase order items.
     */
    private function calculateTotals(array $items, array $data): array
    {
        return $this->totalCalculationService->calculateLineTotals($items, $data);
    }

    /**
     * Generate unique PO code.
     */
    private function generatePoCode(): string
    {
        $prefix = config('purchase.po.code_prefix', 'PO-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->purchaseOrderRepository->findByPoCode($code) !== null
        );
    }
}
