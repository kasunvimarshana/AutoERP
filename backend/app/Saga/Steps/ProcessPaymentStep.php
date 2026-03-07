<?php

namespace App\Saga\Steps;

use App\Models\Order;
use App\Saga\Contracts\SagaInterface;
use Illuminate\Support\Facades\Log;

/**
 * Simulated payment processing step.
 * In production, this would call a payment gateway (Stripe, PayPal, etc.).
 */
class ProcessPaymentStep implements SagaInterface
{
    public function getName(): string
    {
        return 'ProcessPayment';
    }

    public function execute(array &$context): array
    {
        $order = $context['order'] ?? null;

        if (!$order) {
            throw new \RuntimeException('Order not found in saga context for payment processing.');
        }

        // ── Payment gateway integration point ─────────────────────────────
        // In production: call Stripe/PayPal/etc. here and store payment intent ID
        $paymentIntentId = 'pi_' . bin2hex(random_bytes(10)); // Simulated

        // Update order payment status
        $order->update([
            'payment_status' => Order::PAYMENT_PAID,
            'status'         => Order::STATUS_CONFIRMED,
            'metadata'       => array_merge($order->metadata ?? [], [
                'payment_intent_id' => $paymentIntentId,
                'payment_method'    => $context['payment_method'] ?? 'card',
                'paid_at'           => now()->toIso8601String(),
            ]),
        ]);

        $context['payment_intent_id'] = $paymentIntentId;
        $context['order']             = $order->fresh('items');

        Log::info("[ProcessPaymentStep] Payment processed for order #{$order->order_number}", [
            'payment_intent_id' => $paymentIntentId,
        ]);

        return $context;
    }

    public function compensate(array &$context): void
    {
        // Refund the payment in a real implementation
        if (!empty($context['payment_intent_id'])) {
            Log::info("[ProcessPaymentStep] Refunding payment intent: {$context['payment_intent_id']}");
            // PaymentGateway::refund($context['payment_intent_id']);
            unset($context['payment_intent_id']);
        }

        // Revert order payment status
        if (!empty($context['order_id'])) {
            Order::withoutGlobalScope('tenant')
                 ->where('id', $context['order_id'])
                 ->update([
                     'payment_status' => Order::PAYMENT_FAILED,
                     'status'         => Order::STATUS_CANCELLED,
                 ]);
        }
    }
}
