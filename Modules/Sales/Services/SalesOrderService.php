<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Sales\Entities\SalesOrder;
use Modules\Sales\Repositories\Contracts\SalesOrderLineRepositoryInterface;
use Modules\Sales\Repositories\Contracts\SalesOrderRepositoryInterface;

/**
 * Sales Order Service
 *
 * Handles business logic for sales order processing, calculations, and confirmations.
 */
class SalesOrderService extends BaseService
{
    /**
     * SalesOrderService constructor.
     */
    public function __construct(
        protected SalesOrderRepositoryInterface $salesOrderRepository,
        protected SalesOrderLineRepositoryInterface $salesOrderLineRepository
    ) {}

    /**
     * Get paginated sales orders.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->salesOrderRepository->paginate($perPage);
    }

    /**
     * Create a new sales order.
     */
    public function create(array $data): SalesOrder
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

            $salesOrder = $this->salesOrderRepository->create($data);

            $this->logInfo('Sales order created', [
                'sales_order_id' => $salesOrder->id,
                'order_number' => $salesOrder->order_number,
            ]);

            return $salesOrder;
        });
    }

    /**
     * Create a sales order with lines.
     */
    public function createWithLines(array $orderData, array $lines): SalesOrder
    {
        return $this->executeInTransaction(function () use ($orderData, $lines) {
            $salesOrder = $this->create($orderData);

            // Create order lines
            foreach ($lines as $lineData) {
                $lineData['sales_order_id'] = $salesOrder->id;
                $this->salesOrderLineRepository->create($lineData);
            }

            // Recalculate totals
            $salesOrder->calculateTotals();

            return $salesOrder->fresh('lines');
        });
    }

    /**
     * Update an existing sales order.
     */
    public function update(int $id, array $data): SalesOrder
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $salesOrder = $this->salesOrderRepository->update($id, $data);

            $this->logInfo('Sales order updated', [
                'sales_order_id' => $salesOrder->id,
            ]);

            return $salesOrder;
        });
    }

    /**
     * Delete a sales order.
     */
    public function delete(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            // Delete associated lines first
            $this->salesOrderLineRepository->deleteByOrder($id);

            $result = $this->salesOrderRepository->delete($id);

            if ($result) {
                $this->logInfo('Sales order deleted', [
                    'sales_order_id' => $id,
                ]);
            }

            return $result;
        });
    }

    /**
     * Find sales order by ID.
     */
    public function findById(int $id, bool $withLines = false): ?SalesOrder
    {
        if ($withLines) {
            return $this->salesOrderRepository->findWithLines($id);
        }

        return $this->salesOrderRepository->findById($id);
    }

    /**
     * Confirm a sales order.
     */
    public function confirm(int $id): SalesOrder
    {
        return $this->executeInTransaction(function () use ($id) {
            $salesOrder = $this->salesOrderRepository->findById($id);

            if (! $salesOrder) {
                throw new \Exception("Sales order with ID {$id} not found.");
            }

            if ($salesOrder->status === 'confirmed') {
                throw new \Exception('Sales order is already confirmed.');
            }

            $salesOrder->confirm();

            $this->logInfo('Sales order confirmed', [
                'sales_order_id' => $salesOrder->id,
                'order_number' => $salesOrder->order_number,
            ]);

            return $salesOrder;
        });
    }

    /**
     * Calculate order totals.
     */
    public function calculateTotals(int $id): SalesOrder
    {
        return $this->executeInTransaction(function () use ($id) {
            $salesOrder = $this->salesOrderRepository->findById($id);

            if (! $salesOrder) {
                throw new \Exception("Sales order with ID {$id} not found.");
            }

            $salesOrder->calculateTotals();

            return $salesOrder->fresh();
        });
    }

    /**
     * Get sales orders by customer.
     */
    public function getOrdersByCustomer(int $customerId): Collection
    {
        return $this->salesOrderRepository->getOrdersByCustomer($customerId);
    }

    /**
     * Get sales orders by status.
     */
    public function getOrdersByStatus(string $status): Collection
    {
        return $this->salesOrderRepository->getOrdersByStatus($status);
    }

    /**
     * Search sales orders.
     */
    public function search(string $query): Collection
    {
        return $this->salesOrderRepository->search($query);
    }

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'SO';
        $year = date('Y');

        // Get the last order number for this year
        $lastOrder = $this->salesOrderRepository
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
