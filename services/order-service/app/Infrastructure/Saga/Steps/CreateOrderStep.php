<?php

declare(strict_types=1);

namespace App\Infrastructure\Saga\Steps;

use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Order\Saga\SagaStepInterface;
use Illuminate\Support\Facades\Log;

/**
 * CreateOrderStep
 *
 * Step 1 of the Order Saga.
 * Creates the order record in 'pending' status.
 * Compensation: sets the order status to 'cancelled'.
 */
class CreateOrderStep implements SagaStepInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function name(): string
    {
        return 'create_order';
    }

    public function execute(array &$context): void
    {
        $order = $this->orderRepository->create([
            'tenant_id'       => $context['tenant_id'],
            'user_id'         => $context['user_id'],
            'status'          => 'pending',
            'currency'        => $context['currency']        ?? 'USD',
            'subtotal'        => $context['subtotal']        ?? 0,
            'tax_amount'      => $context['tax_amount']      ?? 0,
            'shipping_amount' => $context['shipping_amount'] ?? 0,
            'total_amount'    => $context['total_amount']    ?? 0,
            'notes'           => $context['notes']           ?? null,
            'metadata'        => $context['metadata']        ?? [],
            'shipping_address'=> $context['shipping_address'] ?? [],
        ]);

        // Persist order ID in context so subsequent steps can reference it
        $context['order_id'] = $order->id;

        Log::info("CreateOrderStep: Order [{$order->id}] created in pending state.");
    }

    public function compensate(array &$context): void
    {
        if (empty($context['order_id'])) {
            return; // Nothing to rollback
        }

        $this->orderRepository->update($context['order_id'], [
            'status'             => 'cancelled',
            'cancellation_reason'=> 'Saga rollback: order creation compensation',
        ]);

        Log::info("CreateOrderStep compensation: Order [{$context['order_id']}] cancelled.");
    }
}
