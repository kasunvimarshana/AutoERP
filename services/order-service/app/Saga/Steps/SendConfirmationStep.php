<?php

namespace App\Saga\Steps;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Saga\Contracts\SagaStepInterface;
use Illuminate\Support\Facades\Log;

class SendConfirmationStep implements SagaStepInterface
{
    public function getName(): string
    {
        return 'send_confirmation';
    }

    public function execute(array $context): array
    {
        $order = $context['order'] ?? null;

        if ($order !== null) {
            OrderCreated::dispatch($order);
            Log::info('SendConfirmationStep: OrderCreated event dispatched', [
                'order_id' => $order->id ?? null,
            ]);
        } else {
            Log::warning('SendConfirmationStep: no order in context, skipping event dispatch');
        }

        return $context;
    }

    public function compensate(array $context): void
    {
        $orderId = $context['order_id'] ?? ($context['order']->id ?? null);

        if ($orderId !== null) {
            OrderCancelled::dispatch($orderId, 'Order cancelled due to saga rollback');
            Log::info('SendConfirmationStep: OrderCancelled event dispatched', [
                'order_id' => $orderId,
            ]);
        } else {
            Log::warning('SendConfirmationStep: no order_id in context, skipping cancellation event');
        }
    }
}
