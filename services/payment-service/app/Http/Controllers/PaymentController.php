<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Charge a customer as part of the Order Saga.
     * Called synchronously by the Order Service.
     */
    public function charge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id'    => 'required|string',
            'saga_id'     => 'required|string',
            'tenant_id'   => 'required|string',
            'customer_id' => 'required|string',
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'required|string|size:3',
        ]);

        // Idempotency: return existing payment if already processed
        $existing = Payment::where('order_id', $data['order_id'])->first();
        if ($existing && $existing->status === Payment::STATUS_PROCESSED) {
            return response()->json([
                'payment_id' => $existing->id,
                'status'     => $existing->status,
            ]);
        }

        $payment = Payment::create([
            ...$data,
            'status'             => Payment::STATUS_PENDING,
            'provider_reference' => 'PAY-' . strtoupper(Str::random(12)),
        ]);

        $success = $this->processWithGateway($payment);

        if ($success) {
            $payment->update(['status' => Payment::STATUS_PROCESSED]);
            Log::info('[Payment] Charged successfully', ['payment_id' => $payment->id]);

            return response()->json([
                'payment_id' => $payment->id,
                'status'     => $payment->status,
            ]);
        }

        $payment->update(['status' => Payment::STATUS_FAILED]);
        Log::error('[Payment] Charge failed', ['payment_id' => $payment->id]);

        return response()->json(
            ['error' => 'Payment processing failed.', 'payment_id' => $payment->id],
            402
        );
    }

    /**
     * Refund a payment (Saga compensation step).
     */
    public function refund(Request $request, string $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== Payment::STATUS_PROCESSED) {
            return response()->json(
                ['error' => 'Payment is not in a refundable state.'],
                409
            );
        }

        $refundRef = 'REF-' . strtoupper(Str::random(12));
        $payment->update([
            'status'    => Payment::STATUS_REFUNDED,
            'refund_id' => $refundRef,
            'metadata'  => array_merge($payment->metadata ?? [], [
                'refund_reason' => $request->input('reason', 'unspecified'),
            ]),
        ]);

        Log::info('[Payment][Compensation] Refunded', [
            'payment_id' => $payment->id,
            'refund_id'  => $refundRef,
        ]);

        return response()->json(['refund_id' => $refundRef, 'status' => 'refunded']);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Payment::findOrFail($id));
    }

    /**
     * Simulate gateway call.
     * In production, integrate Stripe / Braintree / etc.
     *
     * Failure simulation: amounts whose cent value mod 10 equals FAILURE_REMAINDER (default 7)
     * result in a declined charge. This triggers a 10% failure rate across typical price points,
     * demonstrating saga compensation without a real payment gateway.
     * Override via PAYMENT_FAILURE_REMAINDER env var (set to -1 to disable failures).
     */
    private function processWithGateway(Payment $payment): bool
    {
        $failureRemainder = (int) env('PAYMENT_FAILURE_REMAINDER', 7);
        if ($failureRemainder < 0) {
            return true; // Failures disabled
        }

        return (int) ($payment->amount * 100) % 10 !== $failureRemainder;
    }
}
