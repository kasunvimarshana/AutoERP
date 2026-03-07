<?php

declare(strict_types=1);

namespace App\Saga\Steps;

use App\Contracts\SagaStepInterface;
use App\Exceptions\SagaStepException;
use App\Models\Payment;
use App\Saga\SagaContext;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Saga Step 2: Process payment for the order.
 *
 * Forward:    Create a Payment record and call payment gateway.
 * Compensate: Refund / void the payment.
 *
 * In a real system this would integrate with Stripe/PayPal etc.
 * Here we simulate the payment gateway call with a local Payment model.
 *
 * Context reads:
 *   - order_id, tenant_id, total_amount, currency, payment_method
 *
 * Context writes:
 *   - payment_id (string UUID of the created Payment record)
 */
final class ProcessPaymentStep implements SagaStepInterface
{
    public function getName(): string
    {
        return 'ProcessPayment';
    }

    public function execute(SagaContext $context): void
    {
        $orderId       = $context->get('order_id');
        $tenantId      = $context->get('tenant_id');
        $totalAmount   = $context->get('total_amount');
        $currency      = $context->get('currency', 'USD');
        $paymentMethod = $context->get('payment_method', 'credit_card');

        // Simulate payment gateway interaction
        $gatewayTransactionId = Str::uuid()->toString();

        $payment = Payment::create([
            'order_id'               => $orderId,
            'tenant_id'              => $tenantId,
            'amount'                 => $totalAmount,
            'currency'               => $currency,
            'payment_method'         => $paymentMethod,
            'gateway_transaction_id' => $gatewayTransactionId,
            'status'                 => 'captured',
        ]);

        $context->set('payment_id', $payment->id);
        $context->set('gateway_transaction_id', $gatewayTransactionId);

        Log::info("[ProcessPaymentStep] Payment captured", [
            'order_id'   => $orderId,
            'payment_id' => $payment->id,
            'amount'     => $totalAmount,
        ]);
    }

    public function compensate(SagaContext $context): void
    {
        $paymentId = $context->get('payment_id');

        if (!$paymentId) {
            return; // Payment was never created
        }

        $payment = Payment::find($paymentId);

        if ($payment) {
            $payment->update(['status' => 'refunded']);
            Log::info("[ProcessPaymentStep] Payment refunded (compensation)", [
                'payment_id' => $paymentId,
            ]);
        }
    }
}
