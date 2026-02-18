<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Repositories\SalesOrderRepository;

/**
 * Sales Order Service
 *
 * Handles all business logic for sales order management.
 */
class SalesOrderService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected SalesOrderRepository $repository
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get all orders with optional filters.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->repository->query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['from_date'])) {
            $query->where('order_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('order_date', '<=', $filters['to_date']);
        }

        $query->with(['customer', 'items.product']);
        $query->orderBy('order_date', 'desc');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Create a new order.
     */
    public function create(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data) {
            // Generate order number if not provided
            if (empty($data['order_number'])) {
                $data['order_number'] = $this->generateOrderNumber();
            }

            // Calculate totals
            $this->calculateTotals($data);

            // Create order
            $order = $this->repository->create($data);

            // Create order items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $itemData['order_id'] = $order->id;
                    $order->items()->create($itemData);
                }
            }

            return $order->load(['items.product', 'customer']);
        });
    }

    /**
     * Update an existing order.
     */
    public function update(string $id, array $data): SalesOrder
    {
        return DB::transaction(function () use ($id, $data) {
            $order = $this->repository->findOrFail($id);

            // Calculate totals if items changed
            if (isset($data['items'])) {
                $this->calculateTotals($data);

                // Update items
                $order->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $itemData['order_id'] = $order->id;
                    $order->items()->create($itemData);
                }
            }

            $order->update($data);

            return $order->load(['items.product', 'customer']);
        });
    }

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNumber(): string
    {
        $prefix = config('sales.order_prefix', 'SO');
        $year = date('Y');

        // Use database transaction with lock to prevent race condition
        return DB::transaction(function () use ($prefix, $year) {
            // Get the last order number for the current year with a lock
            $lastOrder = $this->repository->query()
                ->where('order_number', 'like', "{$prefix}-{$year}-%")
                ->orderBy('order_number', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastOrder && preg_match('/-(\d+)$/', $lastOrder->order_number, $matches)) {
                $newNumber = (int) $matches[1] + 1;
            } else {
                $newNumber = 1;
            }

            return $prefix.'-'.$year.'-'.str_pad((string) $newNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Calculate order totals.
     */
    protected function calculateTotals(array &$data): void
    {
        $subtotal = 0;
        $taxAmount = 0;

        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount_amount'] ?? 0;
                $itemTax = ($itemSubtotal - $itemDiscount) * ($item['tax_rate'] ?? 0) / 100;

                $data['items'][$key]['total_amount'] = $itemSubtotal - $itemDiscount + $itemTax;

                $subtotal += $itemSubtotal;
                $taxAmount += $itemTax;
            }
        }

        $data['subtotal'] = $subtotal;
        $data['tax_amount'] = $taxAmount;
        $data['discount_amount'] = $data['discount_amount'] ?? 0;
        $data['total_amount'] = $subtotal + $taxAmount - $data['discount_amount'];
    }
}
