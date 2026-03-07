<?php

declare(strict_types=1);

namespace App\Saga\Steps;

use App\Contracts\SagaStepInterface;
use App\Saga\SagaContext;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Saga Step 4: Send order confirmation notification.
 *
 * Forward:    POST /api/v1/notifications  → Notification Service (Node.js)
 * Compensate: POST /api/v1/notifications/cancel  → send cancellation notice
 *
 * Note: Notification sending is best-effort. A failure here does NOT
 * roll back the payment or inventory reservation.  In a real system
 * this would be a separate outbox/event pattern.
 *
 * Context reads:
 *   - order_id, tenant_id, user_email, order_confirmed
 */
final class SendNotificationStep implements SagaStepInterface
{
    private readonly Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'base_uri' => config('services.notification.url'),
            'timeout'  => 5.0,
        ]);
    }

    public function getName(): string
    {
        return 'SendNotification';
    }

    public function execute(SagaContext $context): void
    {
        $orderId    = $context->get('order_id');
        $tenantId   = $context->get('tenant_id');
        $userEmail  = $context->get('user_email');

        try {
            $this->httpClient->post('/api/v1/notifications', [
                'json' => [
                    'type'       => 'order_confirmed',
                    'order_id'   => $orderId,
                    'tenant_id'  => $tenantId,
                    'recipient'  => $userEmail,
                    'payload'    => [
                        'order_id'     => $orderId,
                        'confirmed_at' => now()->toIso8601String(),
                    ],
                ],
                'headers' => [
                    'Accept'             => 'application/json',
                    'X-Internal-Service' => 'order-service',
                ],
            ]);

            Log::info("[SendNotificationStep] Notification sent", ['order_id' => $orderId]);
        } catch (\Throwable $e) {
            // Non-fatal: log but don't fail the Saga
            Log::warning("[SendNotificationStep] Failed to send notification (non-fatal)", [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    public function compensate(SagaContext $context): void
    {
        $orderId   = $context->get('order_id');
        $userEmail = $context->get('user_email');

        try {
            $this->httpClient->post('/api/v1/notifications', [
                'json' => [
                    'type'      => 'order_cancelled',
                    'order_id'  => $orderId,
                    'recipient' => $userEmail,
                    'payload'   => [
                        'order_id'     => $orderId,
                        'cancelled_at' => now()->toIso8601String(),
                    ],
                ],
                'headers' => [
                    'Accept'             => 'application/json',
                    'X-Internal-Service' => 'order-service',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning("[SendNotificationStep] Compensation notification failed (non-fatal)", [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
