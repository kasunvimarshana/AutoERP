<?php

declare(strict_types=1);

namespace App\Saga\Steps;

use App\Contracts\SagaStepInterface;
use App\Exceptions\SagaStepException;
use App\Models\Order;
use App\Saga\SagaContext;
use Illuminate\Support\Facades\Log;

/**
 * Saga Step 3: Confirm the order after successful payment.
 *
 * Forward:    Transition Order status from 'pending' → 'confirmed'.
 * Compensate: Transition Order status to 'cancelled'.
 *
 * Context reads:
 *   - order_id
 *
 * Context writes:
 *   - order_confirmed (bool)
 */
final class ConfirmOrderStep implements SagaStepInterface
{
    public function getName(): string
    {
        return 'ConfirmOrder';
    }

    public function execute(SagaContext $context): void
    {
        $orderId = $context->get('order_id');
        $order   = Order::find($orderId);

        if (!$order) {
            throw new SagaStepException("Order {$orderId} not found during confirmation.");
        }

        $order->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $context->set('order_confirmed', true);

        Log::info("[ConfirmOrderStep] Order confirmed", ['order_id' => $orderId]);
    }

    public function compensate(SagaContext $context): void
    {
        $orderId = $context->get('order_id');
        $order   = Order::find($orderId);

        if ($order) {
            $order->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
            ]);

            Log::info("[ConfirmOrderStep] Order cancelled (compensation)", ['order_id' => $orderId]);
        }
    }
}
