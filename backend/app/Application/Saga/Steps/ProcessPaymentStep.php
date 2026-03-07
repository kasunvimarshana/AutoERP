<?php

declare(strict_types=1);

namespace App\Application\Saga\Steps;

use App\Application\Saga\Contracts\SagaInterface;
use Illuminate\Support\Facades\Log;

/**
 * Saga step: Process payment for the order.
 *
 * In a real implementation this would call a payment gateway.
 * The compensate step voids / refunds the charge.
 */
final class ProcessPaymentStep implements SagaInterface
{
    public function name(): string
    {
        return 'ProcessPayment';
    }

    public function execute(array $context): array
    {
        // TODO: Integrate with payment gateway (Stripe, Braintree, etc.)
        // Simulate successful payment for now.
        $paymentIntentId = 'pi_' . uniqid('', true);

        Log::info("[ProcessPaymentStep] Payment processed. Intent: {$paymentIntentId}");

        $context['payment_intent_id'] = $paymentIntentId;
        $context['payment_status']    = 'captured';

        return $context;
    }

    public function compensate(array $context): void
    {
        $paymentIntentId = $context['payment_intent_id'] ?? null;

        if ($paymentIntentId) {
            // TODO: Call payment gateway void/refund API.
            Log::info("[ProcessPaymentStep:compensate] Payment voided. Intent: {$paymentIntentId}");
        }

        Log::info('[ProcessPaymentStep:compensate] Payment compensation complete.');
    }
}
