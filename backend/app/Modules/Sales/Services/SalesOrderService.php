<?php

declare(strict_types=1);

namespace App\Modules\Sales\Services;

use App\Core\Services\BaseService;
use App\Modules\Sales\Repositories\SalesOrderRepositoryInterface;
use App\Modules\Sales\Repositories\QuoteRepositoryInterface;
use App\Modules\CRM\Repositories\CustomerRepositoryInterface;
use App\Modules\Inventory\Services\InventoryService;
use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SalesOrderService extends BaseService
{
    protected CustomerRepositoryInterface $customerRepository;
    protected QuoteRepositoryInterface $quoteRepository;
    protected InventoryService $inventoryService;

    public function __construct(
        SalesOrderRepositoryInterface $repository,
        CustomerRepositoryInterface $customerRepository,
        QuoteRepositoryInterface $quoteRepository,
        InventoryService $inventoryService
    ) {
        parent::__construct($repository);
        $this->customerRepository = $customerRepository;
        $this->quoteRepository = $quoteRepository;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Create sales order with items.
     */
    public function createOrderWithItems(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Calculate totals
            $calculations = $this->calculateOrderTotals($items, $data);
            
            $orderData = array_merge($data, [
                'order_number' => $this->generateOrderNumber(),
                'status' => $data['status'] ?? 'draft',
                'order_date' => $data['order_date'] ?? now(),
                'subtotal' => $calculations['subtotal'],
                'discount_amount' => $calculations['discount_amount'],
                'tax_amount' => $calculations['tax_amount'],
                'total_amount' => $calculations['total_amount'],
                'payment_status' => 'unpaid',
                'fulfillment_status' => 'unfulfilled',
                'created_by' => Auth::id(),
            ]);

            $order = $this->repository->create($orderData);

            // Create order items
            foreach ($items as $item) {
                $itemCalculations = $this->calculateItemTotals($item);
                
                $order->items()->create(array_merge($item, [
                    'tenant_id' => $order->tenant_id,
                    'discount_amount' => $itemCalculations['discount_amount'],
                    'tax_amount' => $itemCalculations['tax_amount'],
                    'subtotal' => $itemCalculations['subtotal'],
                    'total' => $itemCalculations['total'],
                    'quantity_fulfilled' => 0,
                    'quantity_invoiced' => 0,
                ]));
            }

            return $order->load(['items', 'customer', 'currency', 'quote']);
        });
    }

    /**
     * Create sales order from quote.
     */
    public function createFromQuote(int $quoteId): mixed
    {
        return DB::transaction(function () use ($quoteId) {
            $quote = $this->quoteRepository->findOrFail($quoteId);

            if ($quote->isConverted()) {
                throw new ServiceException('Quote is already converted');
            }

            if ($quote->isExpired()) {
                throw new ServiceException('Quote has expired');
            }

            // Prepare order data from quote
            $orderData = [
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
                'customer_contact_name' => $quote->customer_contact_name,
                'customer_email' => $quote->customer_email,
                'customer_phone' => $quote->customer_phone,
                'organization_id' => $quote->organization_id,
                'branch_id' => $quote->branch_id,
                'currency_id' => $quote->currency_id,
                'exchange_rate' => $quote->exchange_rate,
                'subtotal' => $quote->subtotal,
                'discount_type' => $quote->discount_type,
                'discount_value' => $quote->discount_value,
                'discount_amount' => $quote->discount_amount,
                'tax_amount' => $quote->tax_amount,
                'shipping_amount' => $quote->shipping_amount,
                'adjustment_amount' => $quote->adjustment_amount,
                'total_amount' => $quote->total_amount,
                'billing_address' => $quote->billing_address,
                'shipping_address' => $quote->shipping_address,
                'notes' => $quote->notes,
                'terms_and_conditions' => $quote->terms_and_conditions,
                'items' => [],
            ];

            // Prepare items
            foreach ($quote->items as $quoteItem) {
                $orderData['items'][] = [
                    'product_id' => $quoteItem->product_id,
                    'variant_id' => $quoteItem->variant_id,
                    'item_name' => $quoteItem->item_name,
                    'item_description' => $quoteItem->item_description,
                    'quantity' => $quoteItem->quantity,
                    'unit_id' => $quoteItem->unit_id,
                    'unit_price' => $quoteItem->unit_price,
                    'discount_type' => $quoteItem->discount_type,
                    'discount_value' => $quoteItem->discount_value,
                    'discount_amount' => $quoteItem->discount_amount,
                    'tax_rate_id' => $quoteItem->tax_rate_id,
                    'tax_percentage' => $quoteItem->tax_percentage,
                    'tax_amount' => $quoteItem->tax_amount,
                    'subtotal' => $quoteItem->subtotal,
                    'total' => $quoteItem->total,
                ];
            }

            // Create the order
            $order = $this->createOrderWithItems($orderData);

            // Mark quote as converted
            $this->quoteRepository->update($quoteId, [
                'status' => 'converted',
                'converted_at' => now(),
            ]);

            return $order;
        });
    }

    /**
     * Update order with items.
     */
    public function updateOrderWithItems(int $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $order = $this->repository->findOrFail($id);

            if (!$order->isEditable()) {
                throw new ServiceException('Order cannot be edited in current status');
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            // Calculate totals
            $calculations = $this->calculateOrderTotals($items, $data);
            
            $orderData = array_merge($data, [
                'subtotal' => $calculations['subtotal'],
                'discount_amount' => $calculations['discount_amount'],
                'tax_amount' => $calculations['tax_amount'],
                'total_amount' => $calculations['total_amount'],
                'updated_by' => Auth::id(),
            ]);

            $order = $this->repository->update($id, $orderData);

            // Update items
            if (!empty($items)) {
                $order->items()->delete();
                
                foreach ($items as $item) {
                    $itemCalculations = $this->calculateItemTotals($item);
                    
                    $order->items()->create(array_merge($item, [
                        'tenant_id' => $order->tenant_id,
                        'discount_amount' => $itemCalculations['discount_amount'],
                        'tax_amount' => $itemCalculations['tax_amount'],
                        'subtotal' => $itemCalculations['subtotal'],
                        'total' => $itemCalculations['total'],
                        'quantity_fulfilled' => 0,
                        'quantity_invoiced' => 0,
                    ]));
                }
            }

            return $order->load(['items', 'customer', 'currency']);
        });
    }

    /**
     * Approve sales order.
     */
    public function approveOrder(int $id): mixed
    {
        return DB::transaction(function () use ($id) {
            $order = $this->repository->findOrFail($id);

            if ($order->status !== 'pending') {
                throw new ServiceException('Only pending orders can be approved');
            }

            return $this->repository->update($id, [
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Reserve inventory for order.
     */
    public function reserveInventory(int $id): mixed
    {
        return DB::transaction(function () use ($id) {
            $order = $this->repository->findOrFail($id);

            if (!$order->isApproved()) {
                throw new ServiceException('Only approved orders can reserve inventory');
            }

            foreach ($order->items as $item) {
                if ($item->product && $item->product->track_inventory) {
                    $this->inventoryService->stockOut([
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'branch_id' => $order->branch_id,
                        'transaction_type' => 'sales_order_reservation',
                        'reference_type' => 'sales_order',
                        'reference_id' => $order->id,
                        'notes' => "Reserved for Sales Order #{$order->order_number}",
                    ]);
                }
            }

            return $order;
        });
    }

    /**
     * Fulfill order item.
     */
    public function fulfillItem(int $orderId, int $itemId, float $quantity): mixed
    {
        return DB::transaction(function () use ($orderId, $itemId, $quantity) {
            $order = $this->repository->findOrFail($orderId);
            $item = $order->items()->findOrFail($itemId);

            $remainingQty = (float)$item->quantity - (float)$item->quantity_fulfilled;
            
            if ($quantity > $remainingQty) {
                throw new ServiceException('Fulfillment quantity exceeds remaining quantity');
            }

            $item->update([
                'quantity_fulfilled' => (float)$item->quantity_fulfilled + $quantity,
            ]);

            // Update order fulfillment status
            $this->updateFulfillmentStatus($order);

            return $order->fresh(['items']);
        });
    }

    /**
     * Update order fulfillment status.
     */
    protected function updateFulfillmentStatus($order): void
    {
        $totalItems = $order->items->count();
        $fulfilledItems = $order->items->filter(fn($item) => $item->isFullyFulfilled())->count();
        $partiallyFulfilledItems = $order->items->filter(fn($item) => $item->isPartiallyFulfilled())->count();

        if ($fulfilledItems === $totalItems) {
            $status = 'fulfilled';
        } elseif ($fulfilledItems > 0 || $partiallyFulfilledItems > 0) {
            $status = 'partially_fulfilled';
        } else {
            $status = 'unfulfilled';
        }

        $this->repository->update($order->id, [
            'fulfillment_status' => $status,
        ]);
    }

    /**
     * Calculate item totals (same as QuoteService).
     */
    protected function calculateItemTotals(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $subtotal = $quantity * $unitPrice;

        $discountAmount = 0;
        if (isset($item['discount_type']) && isset($item['discount_value'])) {
            if ($item['discount_type'] === 'percentage') {
                $discountAmount = $subtotal * ((float) $item['discount_value'] / 100);
            } elseif ($item['discount_type'] === 'flat') {
                $discountAmount = (float) $item['discount_value'];
            }
        }

        $afterDiscount = $subtotal - $discountAmount;

        $taxAmount = 0;
        if (isset($item['tax_percentage'])) {
            $taxAmount = $afterDiscount * ((float) $item['tax_percentage'] / 100);
        }

        $total = $afterDiscount + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Calculate order totals.
     */
    protected function calculateOrderTotals(array $items, array $headerData): array
    {
        $subtotal = 0;
        $totalTax = 0;

        foreach ($items as $item) {
            $itemCalcs = $this->calculateItemTotals($item);
            $subtotal += $itemCalcs['subtotal'];
            $totalTax += $itemCalcs['tax_amount'];
        }

        $discountAmount = 0;
        if (isset($headerData['discount_type']) && isset($headerData['discount_value'])) {
            if ($headerData['discount_type'] === 'percentage') {
                $discountAmount = $subtotal * ((float) $headerData['discount_value'] / 100);
            } elseif ($headerData['discount_type'] === 'flat') {
                $discountAmount = (float) $headerData['discount_value'];
            }
        }

        $shippingAmount = (float) ($headerData['shipping_amount'] ?? 0);
        $adjustmentAmount = (float) ($headerData['adjustment_amount'] ?? 0);

        $totalAmount = $subtotal - $discountAmount + $totalTax + $shippingAmount + $adjustmentAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => round($totalTax, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Generate unique order number.
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'SO';
        $date = now()->format('Ymd');
        $lastOrder = $this->repository->getModel()
            ->where('order_number', 'like', $prefix . $date . '%')
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }
}
