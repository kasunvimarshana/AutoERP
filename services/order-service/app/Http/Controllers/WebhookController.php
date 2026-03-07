<?php

namespace App\Http\Controllers;

use App\Webhooks\WebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends BaseController
{
    public function __construct(private readonly WebhookDispatcher $dispatcher) {}

    /**
     * POST /api/webhooks/incoming
     * Receive and process inbound webhooks from other microservices.
     * Validates HMAC-SHA256 signature before processing.
     */
    public function receive(Request $request): JsonResponse
    {
        // ---------------------------------------------------------------
        // 1. Validate HMAC signature
        // ---------------------------------------------------------------
        $signature = $request->header('X-Webhook-Signature');
        $secret    = config('services.webhook_secret', '');

        if (! $this->verifySignature($request->getContent(), $signature, $secret)) {
            Log::warning('WebhookController: invalid signature', [
                'ip'    => $request->ip(),
                'event' => $request->input('event'),
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        // ---------------------------------------------------------------
        // 2. Route by event type
        // ---------------------------------------------------------------
        $event   = $request->input('event');
        $payload = $request->input('payload', []);

        Log::info('WebhookController: received event', ['event' => $event]);

        return match ($event) {
            'inventory.updated' => $this->handleInventoryUpdated($payload),
            'product.deleted'   => $this->handleProductDeleted($payload),
            'payment.confirmed' => $this->handlePaymentConfirmed($payload),
            'payment.failed'    => $this->handlePaymentFailed($payload),
            default             => $this->handleUnknownEvent($event),
        };
    }

    // -------------------------------------------------------------------------
    // Event Handlers
    // -------------------------------------------------------------------------

    private function handleInventoryUpdated(array $payload): JsonResponse
    {
        try {
            $productId = $payload['product_id'] ?? null;
            $quantity  = $payload['quantity'] ?? null;

            Log::info('WebhookController: inventory updated', [
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);

            // Dispatch event so listeners can act (e.g. flag back-ordered items)
            event(new \App\Events\OrderStatusChanged(
                orderId: 0,
                oldStatus: 'processing',
                newStatus: 'processing',
                metadata: ['inventory_update' => $payload]
            ));

            return response()->json(['success' => true, 'message' => 'ACK inventory.updated'], 200);
        } catch (\Throwable $e) {
            Log::error('WebhookController: inventory.updated failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Processing error'], 500);
        }
    }

    private function handleProductDeleted(array $payload): JsonResponse
    {
        try {
            $productId = $payload['product_id'] ?? null;

            Log::info('WebhookController: product deleted', ['product_id' => $productId]);

            // Mark open order items referencing this product as unavailable
            if ($productId) {
                \App\Models\OrderItem::whereHas('order', function ($q) {
                    $q->whereIn('status', ['pending', 'confirmed', 'processing']);
                })
                ->where('product_id', $productId)
                ->update(['attributes->product_deleted' => true]);
            }

            return response()->json(['success' => true, 'message' => 'ACK product.deleted'], 200);
        } catch (\Throwable $e) {
            Log::error('WebhookController: product.deleted failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Processing error'], 500);
        }
    }

    private function handlePaymentConfirmed(array $payload): JsonResponse
    {
        try {
            $orderId = $payload['order_id'] ?? null;
            Log::info('WebhookController: payment confirmed', ['order_id' => $orderId]);

            if ($orderId) {
                $order = \App\Models\Order::find($orderId);
                if ($order && $order->payment_status !== 'paid') {
                    $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);
                }
            }

            return response()->json(['success' => true, 'message' => 'ACK payment.confirmed'], 200);
        } catch (\Throwable $e) {
            Log::error('WebhookController: payment.confirmed failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Processing error'], 500);
        }
    }

    private function handlePaymentFailed(array $payload): JsonResponse
    {
        try {
            $orderId = $payload['order_id'] ?? null;
            Log::info('WebhookController: payment failed', ['order_id' => $orderId]);

            if ($orderId) {
                $order = \App\Models\Order::find($orderId);
                if ($order) {
                    $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
                    event(new \App\Events\OrderCancelled($order->id, 'Payment failed'));
                }
            }

            return response()->json(['success' => true, 'message' => 'ACK payment.failed'], 200);
        } catch (\Throwable $e) {
            Log::error('WebhookController: payment.failed failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Processing error'], 500);
        }
    }

    private function handleUnknownEvent(string $event): JsonResponse
    {
        Log::info('WebhookController: unknown event ignored', ['event' => $event]);

        return response()->json(['success' => true, 'message' => "ACK – event '{$event}' ignored"], 200);
    }

    // -------------------------------------------------------------------------
    // Signature Verification
    // -------------------------------------------------------------------------

    private function verifySignature(string $payload, ?string $signature, string $secret): bool
    {
        if (empty($secret)) {
            // In development with no secret configured, allow all
            return true;
        }

        if (empty($signature)) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
