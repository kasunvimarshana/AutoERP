<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Procurement\Entities\PurchaseOrder;
use Modules\Procurement\Repositories\Contracts\PurchaseOrderLineRepositoryInterface;
use Modules\Procurement\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Procurement\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;

/**
 * Purchase Order Service
 *
 * Handles business logic for PO generation from requisitions and calculations.
 */
class PurchaseOrderService extends BaseService
{
    /**
     * PurchaseOrderService constructor.
     */
    public function __construct(
        protected PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        protected PurchaseOrderLineRepositoryInterface $purchaseOrderLineRepository,
        protected PurchaseRequisitionRepositoryInterface $requisitionRepository
    ) {}

    /**
     * Get paginated purchase orders.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->purchaseOrderRepository->paginate($perPage);
    }

    /**
     * Create a new purchase order.
     */
    public function create(array $data): PurchaseOrder
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate order number if not provided
            if (empty($data['order_number'])) {
                $data['order_number'] = $this->generateOrderNumber();
            }

            // Set default status if not provided
            if (empty($data['status'])) {
                $data['status'] = 'draft';
            }

            // Set default payment status if not provided
            if (empty($data['payment_status'])) {
                $data['payment_status'] = 'unpaid';
            }

            // Initialize amounts if not provided
            $data['subtotal'] = $data['subtotal'] ?? 0;
            $data['tax_amount'] = $data['tax_amount'] ?? 0;
            $data['discount_amount'] = $data['discount_amount'] ?? 0;
            $data['shipping_amount'] = $data['shipping_amount'] ?? 0;
            $data['total_amount'] = $data['total_amount'] ?? 0;

            $purchaseOrder = $this->purchaseOrderRepository->create($data);

            $this->logInfo('Purchase order created', [
                'purchase_order_id' => $purchaseOrder->id,
                'order_number' => $purchaseOrder->order_number,
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Create a purchase order with lines.
     */
    public function createWithLines(array $orderData, array $lines): PurchaseOrder
    {
        return $this->executeInTransaction(function () use ($orderData, $lines) {
            $purchaseOrder = $this->create($orderData);

            // Create order lines
            foreach ($lines as $lineData) {
                $lineData['purchase_order_id'] = $purchaseOrder->id;
                $this->purchaseOrderLineRepository->create($lineData);
            }

            // Recalculate totals
            $purchaseOrder->calculateTotals();

            return $purchaseOrder->fresh('lines');
        });
    }

    /**
     * Create purchase order from requisition.
     */
    public function createFromRequisition(int $requisitionId, array $additionalData = []): PurchaseOrder
    {
        return $this->executeInTransaction(function () use ($requisitionId, $additionalData) {
            $requisition = $this->requisitionRepository->findWithLines($requisitionId);

            if (! $requisition) {
                throw new \Exception("Purchase requisition with ID {$requisitionId} not found.");
            }

            if (! $requisition->isApproved()) {
                throw new \Exception('Purchase requisition must be approved before creating a purchase order.');
            }

            // Create purchase order from requisition data
            $orderData = array_merge([
                'purchase_requisition_id' => $requisition->id,
                'supplier_id' => $requisition->supplier_id,
                'currency' => $requisition->currency,
                'order_date' => now(),
            ], $additionalData);

            $purchaseOrder = $this->create($orderData);

            // Create order lines from requisition lines
            foreach ($requisition->lines as $reqLine) {
                $lineData = [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $reqLine->product_id,
                    'description' => $reqLine->description,
                    'quantity' => $reqLine->quantity,
                    'unit_of_measure' => $reqLine->unit_of_measure,
                    'unit_price' => $reqLine->estimated_unit_price,
                    'tax_rate' => 0,
                    'received_quantity' => 0,
                ];

                $this->purchaseOrderLineRepository->create($lineData);
            }

            // Recalculate totals
            $purchaseOrder->calculateTotals();

            $this->logInfo('Purchase order created from requisition', [
                'purchase_order_id' => $purchaseOrder->id,
                'requisition_id' => $requisition->id,
            ]);

            return $purchaseOrder->fresh('lines');
        });
    }

    /**
     * Update an existing purchase order.
     */
    public function update(int $id, array $data): PurchaseOrder
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $purchaseOrder = $this->purchaseOrderRepository->update($id, $data);

            $this->logInfo('Purchase order updated', [
                'purchase_order_id' => $purchaseOrder->id,
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Delete a purchase order.
     */
    public function delete(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            // Delete associated lines first
            $this->purchaseOrderLineRepository->deleteByOrder($id);

            $result = $this->purchaseOrderRepository->delete($id);

            if ($result) {
                $this->logInfo('Purchase order deleted', [
                    'purchase_order_id' => $id,
                ]);
            }

            return $result;
        });
    }

    /**
     * Find purchase order by ID.
     */
    public function findById(int $id, bool $withLines = false): ?PurchaseOrder
    {
        if ($withLines) {
            return $this->purchaseOrderRepository->findWithLines($id);
        }

        return $this->purchaseOrderRepository->findById($id);
    }

    /**
     * Send a purchase order.
     */
    public function send(int $id): PurchaseOrder
    {
        return $this->executeInTransaction(function () use ($id) {
            $purchaseOrder = $this->purchaseOrderRepository->findById($id);

            if (! $purchaseOrder) {
                throw new \Exception("Purchase order with ID {$id} not found.");
            }

            if ($purchaseOrder->status === 'sent') {
                throw new \Exception('Purchase order is already sent.');
            }

            $purchaseOrder->send();

            $this->logInfo('Purchase order sent', [
                'purchase_order_id' => $purchaseOrder->id,
                'order_number' => $purchaseOrder->order_number,
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Confirm a purchase order.
     */
    public function confirm(int $id): PurchaseOrder
    {
        return $this->executeInTransaction(function () use ($id) {
            $purchaseOrder = $this->purchaseOrderRepository->findById($id);

            if (! $purchaseOrder) {
                throw new \Exception("Purchase order with ID {$id} not found.");
            }

            $purchaseOrder->confirm();

            $this->logInfo('Purchase order confirmed', [
                'purchase_order_id' => $purchaseOrder->id,
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Close a purchase order.
     */
    public function close(int $id): PurchaseOrder
    {
        return $this->executeInTransaction(function () use ($id) {
            $purchaseOrder = $this->purchaseOrderRepository->findById($id);

            if (! $purchaseOrder) {
                throw new \Exception("Purchase order with ID {$id} not found.");
            }

            $purchaseOrder->close();

            $this->logInfo('Purchase order closed', [
                'purchase_order_id' => $purchaseOrder->id,
            ]);

            return $purchaseOrder;
        });
    }

    /**
     * Calculate order totals.
     */
    public function calculateTotals(int $id): PurchaseOrder
    {
        return $this->executeInTransaction(function () use ($id) {
            $purchaseOrder = $this->purchaseOrderRepository->findById($id);

            if (! $purchaseOrder) {
                throw new \Exception("Purchase order with ID {$id} not found.");
            }

            $purchaseOrder->calculateTotals();

            return $purchaseOrder->fresh();
        });
    }

    /**
     * Get purchase orders by supplier.
     */
    public function getOrdersBySupplier(int $supplierId): Collection
    {
        return $this->purchaseOrderRepository->getOrdersBySupplier($supplierId);
    }

    /**
     * Get purchase orders by status.
     */
    public function getOrdersByStatus(string $status): Collection
    {
        return $this->purchaseOrderRepository->getOrdersByStatus($status);
    }

    /**
     * Search purchase orders.
     */
    public function search(string $query): Collection
    {
        return $this->purchaseOrderRepository->search($query);
    }

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'PO';
        $year = date('Y');

        // Get the last order number for this year
        $lastOrder = $this->purchaseOrderRepository
            ->getModel()
            ->where('order_number', 'LIKE', "{$prefix}-{$year}-%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            // Extract the sequence number and increment
            $parts = explode('-', $lastOrder->order_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $sequence);
    }
}
