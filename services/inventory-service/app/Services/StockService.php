<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Contracts\Repositories\StockMovementRepositoryInterface;
use App\Contracts\Services\StockServiceInterface;
use App\Domain\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * Stock Service
 *
 * Handles inventory stock operations as a Saga participant.
 * Publishes events to message broker for Saga orchestration.
 *
 * Each method has a corresponding compensation method for rollback.
 */
class StockService implements StockServiceInterface
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
        private readonly StockMovementRepositoryInterface $movementRepository,
        private readonly MessageBrokerInterface $messageBroker,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Reserve stock - Saga Step 1
     * Compensating action: releaseReservation()
     */
    public function reserveStock(string $productId, string $warehouseId, int $quantity, string $sagaId): bool
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $sagaId) {
            $reserved = $this->inventoryRepository->reserveStock($productId, $warehouseId, $quantity);

            if ($reserved) {
                $this->movementRepository->recordMovement([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'movement_type' => StockMovement::TYPES[2], // RESERVE
                    'quantity' => $quantity,
                    'reference_id' => $sagaId,
                    'reference_type' => 'saga',
                    'notes' => "Stock reserved for Saga {$sagaId}",
                ]);

                $this->messageBroker->publish('inventory.stock.reserved', [
                    'saga_id' => $sagaId,
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                    'status' => 'reserved',
                ], ['exchange' => 'saga.events', 'routing_key' => 'saga.inventory.reserved']);

                $this->logger->info('Saga: Stock reserved', compact('sagaId', 'productId', 'quantity'));
            } else {
                $this->messageBroker->publish('inventory.stock.reserve.failed', [
                    'saga_id' => $sagaId,
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                    'status' => 'failed',
                    'reason' => 'Insufficient stock',
                ], ['exchange' => 'saga.events', 'routing_key' => 'saga.inventory.reserve.failed']);

                $this->logger->warning('Saga: Stock reservation failed', compact('sagaId', 'productId', 'quantity'));
            }

            return $reserved;
        });
    }

    /**
     * Release stock reservation - Saga Compensation for reserveStock()
     */
    public function releaseReservation(string $productId, string $warehouseId, int $quantity, string $sagaId): bool
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $sagaId) {
            $released = $this->inventoryRepository->releaseReservation($productId, $warehouseId, $quantity);

            if ($released) {
                $this->movementRepository->recordMovement([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'movement_type' => StockMovement::TYPES[3], // RELEASE
                    'quantity' => $quantity,
                    'reference_id' => $sagaId,
                    'reference_type' => 'saga',
                    'notes' => "Compensation: Release reservation for Saga {$sagaId}",
                ]);

                $this->messageBroker->publish('inventory.stock.released', [
                    'saga_id' => $sagaId,
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                    'status' => 'released',
                ], ['exchange' => 'saga.events', 'routing_key' => 'saga.inventory.released']);

                $this->logger->info('Saga: Stock reservation released (compensation)', compact('sagaId', 'productId'));
            }

            return $released;
        });
    }

    /**
     * Deduct stock - Saga final commit step
     * Compensating action: restoreStock()
     */
    public function deductStock(string $productId, string $warehouseId, int $quantity, string $sagaId): bool
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $sagaId) {
            $deducted = $this->inventoryRepository->deductStock($productId, $warehouseId, $quantity);

            if ($deducted) {
                $this->movementRepository->recordMovement([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'movement_type' => StockMovement::TYPES[1], // OUT
                    'quantity' => $quantity,
                    'reference_id' => $sagaId,
                    'reference_type' => 'saga',
                    'notes' => "Stock deducted for completed Saga {$sagaId}",
                ]);

                $this->messageBroker->publish('inventory.stock.deducted', [
                    'saga_id' => $sagaId,
                    'product_id' => $productId,
                    'status' => 'deducted',
                ], ['exchange' => 'saga.events', 'routing_key' => 'saga.inventory.deducted']);
            }

            return $deducted;
        });
    }

    /**
     * Restore stock - Saga compensation for deductStock()
     */
    public function restoreStock(string $productId, string $warehouseId, int $quantity, string $sagaId): bool
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $sagaId) {
            $item = $this->inventoryRepository->findByProductAndWarehouse($productId, $warehouseId);

            if (!$item) {
                return false;
            }

            $item->increment('quantity_on_hand', $quantity);

            $this->movementRepository->recordMovement([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'movement_type' => StockMovement::TYPES[0], // IN
                'quantity' => $quantity,
                'reference_id' => $sagaId,
                'reference_type' => 'saga',
                'notes' => "Compensation: Restore stock for failed Saga {$sagaId}",
            ]);

            $this->messageBroker->publish('inventory.stock.restored', [
                'saga_id' => $sagaId,
                'product_id' => $productId,
                'status' => 'restored',
            ], ['exchange' => 'saga.events', 'routing_key' => 'saga.inventory.restored']);

            $this->logger->info('Saga: Stock restored (compensation)', compact('sagaId', 'productId', 'quantity'));
            return true;
        });
    }

    /**
     * Check stock availability without reservation.
     */
    public function checkAvailability(string $productId, string $warehouseId, int $quantity): bool
    {
        $item = $this->inventoryRepository->findByProductAndWarehouse($productId, $warehouseId);

        if (!$item) {
            return false;
        }

        return $item->getAvailableQuantityAttribute() >= $quantity;
    }
}
