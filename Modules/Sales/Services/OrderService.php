<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Core\Services\TotalCalculationService;
use Modules\Sales\Enums\InvoiceStatus;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Exceptions\InvalidOrderStatusException;
use Modules\Sales\Models\Order;
use Modules\Sales\Repositories\InvoiceRepository;
use Modules\Sales\Repositories\OrderRepository;

/**
 * Order Service
 *
 * Handles business logic for orders including lifecycle management,
 * status transitions, and invoice creation.
 */
class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private InvoiceRepository $invoiceRepository,
        private CodeGeneratorService $codeGenerator,
        private TotalCalculationService $totalCalculationService
    ) {}

    /**
     * Create a new order with items.
     */
    public function createOrder(array $data, array $items = []): Order
    {
        return TransactionHelper::execute(function () use ($data, $items) {
            // Generate order code if not provided
            if (empty($data['order_code'])) {
                $data['order_code'] = $this->generateOrderCode();
            }

            // Set default status and dates
            $data['status'] = $data['status'] ?? OrderStatus::DRAFT;
            $data['order_date'] = $data['order_date'] ?? now();
            $data['paid_amount'] = $data['paid_amount'] ?? '0.00';

            // Calculate totals if items are provided
            if (! empty($items)) {
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Create order
            $order = $this->orderRepository->create($data);

            // Create items if provided
            if (! empty($items)) {
                foreach ($items as $item) {
                    $item['order_id'] = $order->id;
                    $order->items()->create($item);
                }
                $order->load('items');
            }

            return $order;
        });
    }

    /**
     * Update order and recalculate totals.
     */
    public function updateOrder(string $id, array $data, ?array $items = null): Order
    {
        $order = $this->orderRepository->findOrFail($id);

        if (! $order->canModify()) {
            throw new InvalidOrderStatusException(
                "Order cannot be modified in {$order->status->value} status"
            );
        }

        return TransactionHelper::execute(function () use ($order, $data, $items) {
            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $order->items()->delete();

                // Create new items
                foreach ($items as $item) {
                    $item['order_id'] = $order->id;
                    $order->items()->create($item);
                }

                // Recalculate totals
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Update order
            return $this->orderRepository->update($order->id, $data);
        });
    }

    /**
     * Confirm order (transition from draft/pending to confirmed).
     */
    public function confirmOrder(string $id): Order
    {
        $order = $this->orderRepository->findOrFail($id);

        if (! $order->canConfirm()) {
            throw new InvalidOrderStatusException(
                "Order cannot be confirmed in {$order->status->value} status"
            );
        }

        return $this->orderRepository->update($order->id, [
            'status' => OrderStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Cancel order.
     */
    public function cancelOrder(string $id, ?string $reason = null): Order
    {
        $order = $this->orderRepository->findOrFail($id);

        if (! $order->canCancel()) {
            throw new InvalidOrderStatusException(
                "Order cannot be cancelled in {$order->status->value} status"
            );
        }

        return $this->orderRepository->update($order->id, [
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Complete order.
     */
    public function completeOrder(string $id): Order
    {
        $order = $this->orderRepository->findOrFail($id);

        if (! $order->canComplete()) {
            throw new InvalidOrderStatusException(
                "Order cannot be completed in {$order->status->value} status"
            );
        }

        return $this->orderRepository->update($order->id, [
            'status' => OrderStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Create invoice from order.
     */
    public function createInvoiceFromOrder(string $orderId, array $invoiceData = []): array
    {
        $order = $this->orderRepository->findOrFail($orderId);

        if ($order->status === OrderStatus::DRAFT) {
            throw new InvalidOrderStatusException(
                'Cannot create invoice from draft order'
            );
        }

        if ($order->status === OrderStatus::CANCELLED) {
            throw new InvalidOrderStatusException(
                'Cannot create invoice from cancelled order'
            );
        }

        return TransactionHelper::execute(function () use ($order, $invoiceData) {
            // Generate invoice code
            $invoiceCode = $this->generateInvoiceCode();

            // Calculate due date if not provided
            $dueDate = $invoiceData['due_date'] ?? now()->addDays(
                config('sales.invoice.default_payment_terms_days', 30)
            );

            // Create invoice from order
            $invoice = $this->invoiceRepository->create(array_merge([
                'tenant_id' => $order->tenant_id,
                'organization_id' => $order->organization_id,
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'invoice_code' => $invoiceCode,
                'reference' => $invoiceData['reference'] ?? $order->reference,
                'status' => InvoiceStatus::DRAFT,
                'invoice_date' => $invoiceData['invoice_date'] ?? now(),
                'due_date' => $dueDate,
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_cost' => $order->shipping_cost ?? '0.00',
                'total_amount' => $order->total_amount,
                'paid_amount' => '0.00',
                'notes' => $invoiceData['notes'] ?? $order->notes,
                'terms_conditions' => $invoiceData['terms_conditions'] ?? $order->terms_conditions,
                'created_by' => $invoiceData['created_by'] ?? $order->created_by,
            ], $invoiceData));

            // Copy order items to invoice items
            foreach ($order->items as $orderItem) {
                $invoice->items()->create([
                    'product_id' => $orderItem->product_id,
                    'product_name' => $orderItem->product_name,
                    'description' => $orderItem->description,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'tax_rate' => $orderItem->tax_rate,
                    'discount_amount' => $orderItem->discount_amount,
                    'line_total' => $orderItem->line_total,
                ]);
            }

            return [
                'order' => $order->fresh(['items']),
                'invoice' => $invoice->load('items'),
            ];
        });
    }

    /**
     * Calculate totals for order items.
     */
    private function calculateTotals(array $items, array $data): array
    {
        return $this->totalCalculationService->calculateLineTotals($items, $data);
    }

    /**
     * Delete an order.
     */
    public function deleteOrder(string $id): bool
    {
        $order = $this->orderRepository->findOrFail($id);

        if (! $order->canDelete()) {
            throw new InvalidOrderStatusException(
                "Order cannot be deleted in {$order->status->value} status"
            );
        }

        return TransactionHelper::execute(function () use ($order) {
            // Delete associated items first
            $order->items()->delete();

            // Delete the order
            return $this->orderRepository->delete($order->id);
        });
    }

    /**
     * Generate unique order code.
     */
    private function generateOrderCode(): string
    {
        $prefix = config('sales.order.code_prefix', 'ORD-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->orderRepository->findByCode($code) !== null
        );
    }

    /**
     * Generate unique invoice code.
     */
    private function generateInvoiceCode(): string
    {
        $prefix = config('sales.invoice.code_prefix', 'INV-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->invoiceRepository->findByCode($code) !== null
        );
    }
}
