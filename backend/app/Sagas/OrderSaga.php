<?php
namespace App\Sagas;

use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\SagaInterface;
use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderSaga implements SagaInterface
{
    public function __construct(private MessageBrokerInterface $messageBroker) {}

    public function execute(array $data): array
    {
        $sagaId = Str::uuid()->toString();
        $completedSteps = [];
        $context = ['saga_id' => $sagaId, 'tenant_id' => $data['tenant_id'] ?? null];

        try {
            $this->validateOrder($data);
            $completedSteps[] = 'validate_order';

            $reservations = $this->reserveInventory($data['items'], $data['tenant_id']);
            $completedSteps[] = 'reserve_inventory';
            $context['reservations'] = $reservations;

            $order = $this->createOrder($data, $sagaId, $reservations);
            $completedSteps[] = 'create_order';
            $context['order_id'] = $order->id;

            $this->confirmPayment($order, $data);
            $completedSteps[] = 'confirm_payment';

            $order->update([
                'status' => Order::STATUS_CONFIRMED,
                'saga_state' => array_merge($order->saga_state ?? [], ['completed_steps' => $completedSteps]),
            ]);
            $completedSteps[] = 'confirm_order';

            $this->messageBroker->publish('order.confirmed', [
                'order_id' => $order->id,
                'saga_id' => $sagaId,
                'tenant_id' => $data['tenant_id'],
            ]);

            return ['success' => true, 'order' => $order->fresh(['items', 'items.product']), 'saga_id' => $sagaId];

        } catch (\Exception $e) {
            Log::error("OrderSaga failed. Compensating...", [
                'saga_id' => $sagaId,
                'error' => $e->getMessage(),
                'completed_steps' => $completedSteps,
            ]);

            $this->compensate($sagaId, $completedSteps, $context);

            throw $e;
        }
    }

    public function compensate(string $sagaId, array $completedSteps, array $context): void
    {
        Log::info("OrderSaga compensating", ['saga_id' => $sagaId, 'steps' => $completedSteps]);

        foreach (array_reverse($completedSteps) as $step) {
            try {
                match($step) {
                    'confirm_order' => $this->compensateConfirmOrder($context),
                    'confirm_payment' => $this->compensateConfirmPayment($context),
                    'create_order' => $this->compensateCreateOrder($context),
                    'reserve_inventory' => $this->compensateReserveInventory($context),
                    'validate_order' => null,
                    default => null,
                };
            } catch (\Exception $e) {
                Log::error("Compensation failed for step {$step}", ['error' => $e->getMessage()]);
            }
        }

        $this->messageBroker->publish('order.saga_compensated', [
            'saga_id' => $sagaId,
            'tenant_id' => $context['tenant_id'] ?? null,
        ]);
    }

    private function validateOrder(array $data): void
    {
        if (empty($data['items'])) {
            throw new \InvalidArgumentException('Order must have at least one item');
        }

        foreach ($data['items'] as $item) {
            if (!isset($item['product_id']) || !isset($item['quantity']) || $item['quantity'] <= 0) {
                throw new \InvalidArgumentException('Invalid order item');
            }
        }
    }

    private function reserveInventory(array $items, int $tenantId): array
    {
        $reservations = [];

        foreach ($items as $item) {
            $inventory = Inventory::where('product_id', $item['product_id'])
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$inventory) {
                throw new \RuntimeException("No inventory found for product {$item['product_id']}");
            }

            $available = $inventory->quantity - $inventory->reserved_quantity;
            if ($available < $item['quantity']) {
                throw new \RuntimeException("Insufficient stock for product {$item['product_id']}. Available: {$available}, Requested: {$item['quantity']}");
            }

            $inventory->increment('reserved_quantity', $item['quantity']);
            $reservations[] = [
                'inventory_id' => $inventory->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ];
        }

        return $reservations;
    }

    private function createOrder(array $data, string $sagaId, array $reservations): Order
    {
        return DB::transaction(function () use ($data, $sagaId, $reservations) {
            $order = Order::create([
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'],
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'status' => Order::STATUS_PENDING,
                'total_amount' => 0,
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'saga_state' => ['saga_id' => $sagaId, 'started_at' => now()->toISOString()],
            ]);

            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $reservation = collect($reservations)->firstWhere('product_id', $item['product_id']);
                $product = \App\Models\Product::findOrFail($item['product_id']);
                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'inventory_id' => $reservation['inventory_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);
            return $order;
        });
    }

    private function confirmPayment(Order $order, array $data): void
    {
        $paymentMethod = $data['payment_method'] ?? 'credit_card';
        Log::info("Payment confirmed for order {$order->id}", ['method' => $paymentMethod]);
    }

    private function compensateConfirmOrder(array $context): void
    {
        if (isset($context['order_id'])) {
            Order::find($context['order_id'])?->update(['status' => Order::STATUS_CANCELLED]);
        }
    }

    private function compensateConfirmPayment(array $context): void
    {
        Log::info("Compensating payment for saga {$context['saga_id']}");
    }

    private function compensateCreateOrder(array $context): void
    {
        if (isset($context['order_id'])) {
            Order::find($context['order_id'])?->delete();
        }
    }

    private function compensateReserveInventory(array $context): void
    {
        if (!isset($context['reservations'])) {
            return;
        }

        foreach ($context['reservations'] as $reservation) {
            $inventory = Inventory::find($reservation['inventory_id']);
            if ($inventory) {
                $inventory->decrement('reserved_quantity', min($reservation['quantity'], $inventory->reserved_quantity));
            }
        }
    }
}
