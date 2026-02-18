<?php

declare(strict_types=1);

namespace Modules\Purchasing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\Purchasing\Models\PurchaseOrder;
use Modules\Purchasing\Repositories\PurchaseOrderRepository;

/**
 * Purchase Order Service
 *
 * Handles business logic for purchase order operations.
 */
class PurchaseOrderService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected PurchaseOrderRepository $repository
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get all purchase orders with filters.
     */
    public function list(array $filters = [])
    {
        return $this->repository->list($filters);
    }

    /**
     * Get a purchase order by ID.
     */
    public function find(int $id): ?PurchaseOrder
    {
        return $this->repository->find($id);
    }

    /**
     * Create a new purchase order.
     */
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            // Generate order number if not provided
            if (empty($data['order_number'])) {
                $data['order_number'] = $this->generateOrderNumber();
            }

            // Create purchase order
            $order = $this->repository->create($data);

            // Create line items if provided
            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $order->items()->create($item);
                }
            }

            // Recalculate totals
            $this->recalculateTotals($order);

            return $order->fresh(['items', 'supplier']);
        });
    }

    /**
     * Update a purchase order.
     */
    public function update(int $id, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($id, $data) {
            $order = $this->repository->update($id, $data);

            // Update line items if provided
            if (isset($data['items'])) {
                // Delete existing items
                $order->items()->delete();

                // Create new items
                foreach ($data['items'] as $item) {
                    $order->items()->create($item);
                }
            }

            // Recalculate totals
            $this->recalculateTotals($order);

            return $order->fresh(['items', 'supplier']);
        });
    }

    /**
     * Delete a purchase order.
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Approve a purchase order.
     */
    public function approve(int $id): PurchaseOrder
    {
        $order = $this->find($id);

        if (! $order) {
            throw new \Exception('Purchase order not found');
        }

        if ($order->status !== \Modules\Purchasing\Enums\PurchaseOrderStatus::Draft) {
            throw new \Exception('Only draft orders can be approved');
        }

        return $this->update($id, [
            'status' => \Modules\Purchasing\Enums\PurchaseOrderStatus::Approved,
        ]);
    }

    /**
     * Submit a purchase order (send to supplier).
     */
    public function submit(int $id): PurchaseOrder
    {
        $order = $this->find($id);

        if (! $order) {
            throw new \Exception('Purchase order not found');
        }

        if ($order->status !== \Modules\Purchasing\Enums\PurchaseOrderStatus::Approved) {
            throw new \Exception('Only approved orders can be submitted');
        }

        return $this->update($id, [
            'status' => \Modules\Purchasing\Enums\PurchaseOrderStatus::Submitted,
        ]);
    }

    /**
     * Cancel a purchase order.
     */
    public function cancel(int $id): PurchaseOrder
    {
        return $this->update($id, [
            'status' => \Modules\Purchasing\Enums\PurchaseOrderStatus::Cancelled,
        ]);
    }

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Recalculate order totals.
     */
    protected function recalculateTotals(PurchaseOrder $order): void
    {
        $subtotal = $order->items()->sum(DB::raw('quantity * unit_price'));

        $order->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + ($order->tax_amount ?? 0) - ($order->discount_amount ?? 0),
        ]);
    }
}
