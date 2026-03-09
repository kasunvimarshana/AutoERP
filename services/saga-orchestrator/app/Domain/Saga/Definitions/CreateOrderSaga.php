<?php

declare(strict_types=1);

namespace App\Domain\Saga\Definitions;

use App\Contracts\Saga\SagaDefinitionInterface;

/**
 * Create Order Saga Definition
 *
 * Orchestrates the complete order creation workflow across 4 services.
 *
 * Steps:
 * 1. Create Order (Order Service)         → Compensation: Cancel Order
 * 2. Reserve Inventory (Inventory Service) → Compensation: Release Reservation
 * 3. Process Payment (Payment Service)     → Compensation: Refund Payment
 * 4. Deduct Inventory (Inventory Service)  → Compensation: Restore Stock
 * 5. Send Notification (Notification Service) → No compensation needed (idempotent)
 *
 * If step 3 (Payment) fails:
 *   - Compensate step 2: Release inventory reservation
 *   - Compensate step 1: Cancel the order
 */
class CreateOrderSaga implements SagaDefinitionInterface
{
    public function getType(): string
    {
        return 'create_order';
    }

    /**
     * Build the ordered list of steps with their compensation endpoints.
     *
     * @param  array<string, mixed>  $payload  Must contain: tenant_id, items[], customer_id, payment_method
     * @return array<int, array<string, mixed>>
     */
    public function buildSteps(array $payload): array
    {
        $sagaId = $payload['saga_id'];
        $tenantId = $payload['tenant_id'];

        return [
            [
                'step_order' => 1,
                'step_name' => 'create_order',
                'service' => 'order-service',
                'endpoint' => config('services.order_service') . '/api/v1/orders/saga',
                'compensation_endpoint' => config('services.order_service') . '/api/v1/orders/saga/{order_id}/cancel',
                'request_payload' => [
                    'saga_id' => $sagaId,
                    'tenant_id' => $tenantId,
                    'customer_id' => $payload['customer_id'],
                    'items' => $payload['items'],
                    'shipping_address' => $payload['shipping_address'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                ],
            ],
            [
                'step_order' => 2,
                'step_name' => 'reserve_inventory',
                'service' => 'inventory-service',
                'endpoint' => config('services.inventory_service') . '/api/v1/inventory/stock/reserve',
                'compensation_endpoint' => config('services.inventory_service') . '/api/v1/inventory/stock/release',
                'request_payload' => [
                    'saga_id' => $sagaId,
                    'tenant_id' => $tenantId,
                    'items' => $payload['items'],
                ],
            ],
            [
                'step_order' => 3,
                'step_name' => 'process_payment',
                'service' => 'payment-service',
                'endpoint' => config('services.payment_service') . '/api/v1/payments/process',
                'compensation_endpoint' => config('services.payment_service') . '/api/v1/payments/{payment_id}/refund',
                'request_payload' => [
                    'saga_id' => $sagaId,
                    'tenant_id' => $tenantId,
                    'customer_id' => $payload['customer_id'],
                    'amount' => $payload['total_amount'],
                    'currency' => $payload['currency'] ?? 'USD',
                    'payment_method' => $payload['payment_method'],
                ],
            ],
            [
                'step_order' => 4,
                'step_name' => 'deduct_inventory',
                'service' => 'inventory-service',
                'endpoint' => config('services.inventory_service') . '/api/v1/inventory/stock/deduct',
                'compensation_endpoint' => config('services.inventory_service') . '/api/v1/inventory/stock/restore',
                'request_payload' => [
                    'saga_id' => $sagaId,
                    'tenant_id' => $tenantId,
                    'items' => $payload['items'],
                ],
            ],
            [
                'step_order' => 5,
                'step_name' => 'send_confirmation',
                'service' => 'notification-service',
                'endpoint' => config('services.notification_service') . '/api/v1/notifications/send',
                'compensation_endpoint' => '',  // Notifications are idempotent; no compensation
                'request_payload' => [
                    'saga_id' => $sagaId,
                    'tenant_id' => $tenantId,
                    'customer_id' => $payload['customer_id'],
                    'event' => 'order.confirmed',
                    'template' => 'order_confirmation',
                ],
            ],
        ];
    }
}
