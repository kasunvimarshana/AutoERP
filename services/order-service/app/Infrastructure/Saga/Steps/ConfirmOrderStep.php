<?php

declare(strict_types=1);

namespace App\Infrastructure\Saga\Steps;

use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Order\Saga\SagaStepInterface;
use App\Infrastructure\Messaging\EventPublisher;
use Illuminate\Support\Facades\Log;

/**
 * ConfirmOrderStep
 *
 * Final step of the Order Saga.
 * Marks the order as 'confirmed' and fires an order.completed event.
 * Compensation: cancels the order (payment already refunded by step 3).
 */
class ConfirmOrderStep implements SagaStepInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventPublisher           $eventPublisher,
    ) {}

    public function name(): string
    {
        return 'confirm_order';
    }

    public function execute(array &$context): void
    {
        $this->orderRepository->update($context['order_id'], [
            'status'       => 'confirmed',
            'confirmed_at' => now()->toIso8601String(),
        ]);

        // Publish completion event so downstream services (Shipping, Notification)
        // can react asynchronously.
        $this->eventPublisher->publish('kvsaas.events', 'order.completed', [
            'order_id'       => $context['order_id'],
            'tenant_id'      => $context['tenant_id'],
            'user_id'        => $context['user_id'],
            'reservation_id' => $context['reservation_id'] ?? null,
            'payment_id'     => $context['payment_id']     ?? null,
            'total_amount'   => $context['total_amount']   ?? 0,
        ]);

        Log::info("ConfirmOrderStep: Order [{$context['order_id']}] confirmed.");
    }

    public function compensate(array &$context): void
    {
        if (empty($context['order_id'])) {
            return;
        }

        $this->orderRepository->update($context['order_id'], [
            'status'             => 'cancelled',
            'cancellation_reason'=> 'Saga rollback: confirmation compensation',
        ]);

        $this->eventPublisher->publish('kvsaas.events', 'order.failed', [
            'order_id'  => $context['order_id'],
            'tenant_id' => $context['tenant_id'],
        ]);

        Log::info("ConfirmOrderStep compensation: Order [{$context['order_id']}] rolled back to cancelled.");
    }
}
