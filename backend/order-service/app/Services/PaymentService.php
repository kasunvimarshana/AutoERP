<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Simulated payment service.
 * In production, replace with a real payment gateway (Stripe, PayPal, etc.)
 */
class PaymentService
{
    public function charge(int $orderId, float $amount, int $tenantId): array
    {
        $paymentId = 'pay_'.Str::random(24);
        Log::info('PaymentService: charge simulated', ['order_id' => $orderId, 'amount' => $amount, 'payment_id' => $paymentId]);

        return ['success' => true, 'payment_id' => $paymentId, 'status' => 'captured', 'amount' => $amount];
    }

    public function refund(string $paymentId, float $amount): array
    {
        Log::info('PaymentService: refund simulated', ['payment_id' => $paymentId, 'amount' => $amount]);

        return ['success' => true, 'refund_id' => 'ref_'.Str::random(24), 'status' => 'refunded'];
    }
}
