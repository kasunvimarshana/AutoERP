<?php

declare(strict_types=1);

namespace App\Application\Saga\Steps;

use App\Application\Saga\Contracts\SagaInterface;
use App\Domain\Order\Contracts\OrderRepositoryInterface;
use App\Domain\Order\Events\OrderCreated;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Saga step: Persist the Order record and its line items.
 *
 * Compensate — soft-deletes the order so stock releases can follow.
 */
final class CreateOrderStep implements SagaInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {}

    public function name(): string
    {
        return 'CreateOrder';
    }

    public function execute(array $context): array
    {
        $orderNumber = 'ORD-' . strtoupper(Str::random(10));

        $subtotal = collect($context['items'])->sum(
            fn (array $item) => $item['quantity'] * $item['unit_price']
        );

        $order = $this->orderRepository->create([
            'tenant_id'       => $context['tenant_id'],
            'order_number'    => $orderNumber,
            'customer_id'     => $context['customer_id'],
            'customer_name'   => $context['customer_name'],
            'customer_email'  => $context['customer_email'],
            'status'          => 'confirmed',
            'subtotal'        => $subtotal,
            'tax'             => $context['tax'] ?? 0,
            'discount'        => $context['discount'] ?? 0,
            'total'           => $subtotal + ($context['tax'] ?? 0) - ($context['discount'] ?? 0),
            'currency'        => $context['currency'] ?? 'USD',
            'notes'           => $context['notes'] ?? null,
            'shipping_address'=> $context['shipping_address'] ?? null,
            'billing_address' => $context['billing_address'] ?? null,
            'saga_id'         => $context['saga_id'] ?? null,
            'metadata'        => array_filter([
                'payment_intent_id' => $context['payment_intent_id'] ?? null,
            ]),
        ]);

        $this->orderRepository->attachItems($order->id, $context['items']);

        event(new OrderCreated($order, $context['tenant_id'], $context['customer_id']));

        Log::info("[CreateOrderStep] Order #{$orderNumber} created (id={$order->id}).");

        $context['order_id']     = $order->id;
        $context['order_number'] = $orderNumber;

        return $context;
    }

    public function compensate(array $context): void
    {
        $orderId = $context['order_id'] ?? null;

        if ($orderId) {
            try {
                $this->orderRepository->updateStatus($orderId, 'cancelled', [
                    'cancellation_reason' => 'Saga compensation rollback',
                ]);

                Log::info("[CreateOrderStep:compensate] Order #{$orderId} cancelled.");
            } catch (\Throwable $e) {
                Log::error("[CreateOrderStep:compensate] Failed to cancel order #{$orderId}: {$e->getMessage()}");
            }
        }
    }
}
