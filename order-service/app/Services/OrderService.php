<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\OrderServiceInterface;
use App\Contracts\SagaOrchestratorInterface;
use App\Exceptions\SagaException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Saga\SagaContext;
use App\Saga\SagaOrchestrator;
use App\Saga\Steps\ConfirmOrderStep;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\SendNotificationStep;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Order service – coordinates order creation through the Saga orchestrator.
 *
 * Creates the Order record locally, then delegates the distributed
 * transaction to the SagaOrchestrator which calls Inventory Service,
 * Payment, and Notification Service in sequence.
 */
final class OrderService implements OrderServiceInterface
{
    /**
     * {@inheritDoc}
     *
     * Saga steps (in order):
     *   1. ReserveInventory  – decrement available stock in Inventory Service
     *   2. ProcessPayment    – capture payment via gateway
     *   3. ConfirmOrder      – flip order status to 'confirmed'
     *   4. SendNotification  – send confirmation email via Notification Service
     */
    public function createOrder(string $tenantId, string $userId, array $data): Order
    {
        // ── 1. Persist the order locally in 'pending' state ────────────
        $order = DB::transaction(function () use ($tenantId, $userId, $data): Order {
            $order = Order::create([
                'tenant_id'      => $tenantId,
                'user_id'        => $userId,
                'status'         => 'pending',
                'total_amount'   => $data['total_amount'],
                'currency'       => $data['currency'] ?? 'USD',
                'payment_method' => $data['payment_method'] ?? 'credit_card',
                'notes'          => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order;
        });

        // ── 2. Build Saga context ───────────────────────────────────────
        $sagaId = Str::uuid()->toString();

        $context = new SagaContext([
            'saga_id'        => $sagaId,
            'order_id'       => $order->id,
            'tenant_id'      => $tenantId,
            'user_id'        => $userId,
            'user_email'     => $data['user_email'] ?? null,
            'items'          => $data['items'],
            'total_amount'   => $data['total_amount'],
            'currency'       => $data['currency'] ?? 'USD',
            'payment_method' => $data['payment_method'] ?? 'credit_card',
        ]);

        // ── 3. Execute Saga ─────────────────────────────────────────────
        $orchestrator = (new SagaOrchestrator())
            ->addStep(new ReserveInventoryStep())
            ->addStep(new ProcessPaymentStep())
            ->addStep(new ConfirmOrderStep())
            ->addStep(new SendNotificationStep());

        try {
            $orchestrator->execute($context);
        } catch (SagaException $e) {
            // Saga failed and compensations have already run.
            // Update order status to 'failed'.
            $order->update(['status' => 'failed']);

            Log::error("Order Saga failed", [
                'order_id' => $order->id,
                'saga_id'  => $sagaId,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }

        return $order->fresh(['items', 'payment']);
    }

    /** {@inheritDoc} */
    public function cancelOrder(Order $order): Order
    {
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            throw new \InvalidArgumentException("Order {$order->id} cannot be cancelled in status: {$order->status}");
        }

        $order->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Log::info("Order cancelled", ['order_id' => $order->id]);

        return $order->fresh();
    }

    /** {@inheritDoc} */
    public function listForTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['items', 'payment'])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->paginate($perPage);
    }

    /** {@inheritDoc} */
    public function find(string $id, string $tenantId): ?Order
    {
        return Order::with(['items', 'payment', 'sagaLogs'])
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
