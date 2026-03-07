<?php

namespace App\Saga;

use App\Services\InventoryService;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * InventorySagaHandler
 *
 * Handles incoming SAGA commands (RESERVE_INVENTORY, RELEASE_INVENTORY,
 * FULFILL_INVENTORY) and publishes the corresponding reply events back to the
 * saga.events exchange so the Order Service orchestrator can advance or
 * compensate the saga.
 */
class InventorySagaHandler
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly RabbitMQService  $rabbitMQ,
    ) {}

    // -------------------------------------------------------------------------
    // RESERVE_INVENTORY
    // -------------------------------------------------------------------------

    /**
     * Reserve stock for all items in the order.
     *
     * Expected payload keys: sagaId, orderId, tenantId, items[]
     */
    public function handleReserveInventory(array $payload): void
    {
        $sagaId   = $payload['saga_id']   ?? $payload['sagaId']   ?? null;
        $orderId  = $payload['order_id']  ?? $payload['orderId']  ?? null;
        $tenantId = $payload['tenant_id'] ?? $payload['tenantId'] ?? null;
        $items    = $payload['items']     ?? [];

        Log::info('[InventorySagaHandler] RESERVE_INVENTORY received.', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);

        try {
            $result = $this->inventoryService->reserveStock(
                (string) $sagaId,
                (string) $orderId,
                $items,
                (string) $tenantId,
            );

            if ($result['success']) {
                $this->rabbitMQ->publishEvent('inventory.reserved', [
                    'saga_id'      => $sagaId,
                    'order_id'     => $orderId,
                    'tenant_id'    => $tenantId,
                    'reservations' => $result['reservations'],
                ]);

                Log::info('[InventorySagaHandler] inventory.reserved published.', [
                    'saga_id' => $sagaId,
                ]);
            } else {
                $this->rabbitMQ->publishEvent('inventory.reservation_failed', [
                    'saga_id'  => $sagaId,
                    'order_id' => $orderId,
                    'tenant_id' => $tenantId,
                    'errors'   => $result['errors'],
                    'reason'   => implode('; ', $result['errors']),
                ]);

                Log::warning('[InventorySagaHandler] inventory.reservation_failed published.', [
                    'saga_id' => $sagaId,
                    'errors'  => $result['errors'],
                ]);
            }
        } catch (Throwable $e) {
            Log::error('[InventorySagaHandler] RESERVE_INVENTORY threw exception.', [
                'saga_id' => $sagaId,
                'error'   => $e->getMessage(),
            ]);

            $this->rabbitMQ->publishEvent('inventory.reservation_failed', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
                'tenant_id' => $tenantId,
                'reason'   => $e->getMessage(),
                'errors'   => [$e->getMessage()],
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // RELEASE_INVENTORY
    // -------------------------------------------------------------------------

    /**
     * Release (compensate) previously reserved stock.
     */
    public function handleReleaseInventory(array $payload): void
    {
        $sagaId  = $payload['saga_id']  ?? $payload['sagaId']  ?? null;
        $orderId = $payload['order_id'] ?? $payload['orderId'] ?? null;

        Log::info('[InventorySagaHandler] RELEASE_INVENTORY received.', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);

        try {
            $this->inventoryService->releaseStock(
                (string) $sagaId,
                (string) $orderId,
            );

            $this->rabbitMQ->publishEvent('inventory.released', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
            ]);

            Log::info('[InventorySagaHandler] inventory.released published.', [
                'saga_id' => $sagaId,
            ]);
        } catch (Throwable $e) {
            Log::error('[InventorySagaHandler] RELEASE_INVENTORY threw exception.', [
                'saga_id' => $sagaId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // FULFILL_INVENTORY
    // -------------------------------------------------------------------------

    /**
     * Fulfill reserved stock (order confirmed / shipped).
     */
    public function handleFulfillInventory(array $payload): void
    {
        $sagaId  = $payload['saga_id']  ?? $payload['sagaId']  ?? null;
        $orderId = $payload['order_id'] ?? $payload['orderId'] ?? null;

        Log::info('[InventorySagaHandler] FULFILL_INVENTORY received.', [
            'saga_id'  => $sagaId,
            'order_id' => $orderId,
        ]);

        try {
            $this->inventoryService->fulfillStock(
                (string) $sagaId,
                (string) $orderId,
            );

            $this->rabbitMQ->publishEvent('inventory.fulfilled', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
            ]);

            Log::info('[InventorySagaHandler] inventory.fulfilled published.', [
                'saga_id' => $sagaId,
            ]);
        } catch (Throwable $e) {
            Log::error('[InventorySagaHandler] FULFILL_INVENTORY threw exception.', [
                'saga_id' => $sagaId,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
