<?php

declare(strict_types=1);

namespace App\Application\Inventory\Services;

use App\Infrastructure\Persistence\Models\InventoryItem;
use App\Infrastructure\Persistence\Models\InventoryReservation;
use App\Infrastructure\Persistence\Repositories\InventoryRepository;
use App\Infrastructure\Messaging\EventPublisher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * InventoryService
 *
 * Business logic for stock management, including:
 *  - Listing / filtering inventory items
 *  - Adjusting stock levels
 *  - Creating and releasing reservations (called by Order Saga)
 */
class InventoryService
{
    public function __construct(
        private readonly InventoryRepository $inventoryRepository,
        private readonly EventPublisher      $eventPublisher,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Queries
    // ─────────────────────────────────────────────────────────────────────────

    public function list(
        string $tenantId,
        array  $filters  = [],
        int    $perPage  = 15,
        array  $orderBy  = ['created_at' => 'desc']
    ): LengthAwarePaginator {
        $filters['tenant_id'] = $tenantId;
        return $this->inventoryRepository->paginate($filters, $perPage, ['*'], 'page', null, [], $orderBy);
    }

    public function getByProduct(string $productId, string $tenantId): ?InventoryItem
    {
        return $this->inventoryRepository->findBy([
            'product_id' => $productId,
            'tenant_id'  => $tenantId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Commands
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Adjust stock level (positive = add, negative = deduct).
     */
    public function adjustStock(
        string $productId,
        string $tenantId,
        int    $delta,
        string $reason = 'manual_adjustment'
    ): InventoryItem {
        return DB::transaction(function () use ($productId, $tenantId, $delta, $reason): InventoryItem {
            $item = $this->inventoryRepository->findByProductAndTenant($productId, $tenantId);

            if ($item === null) {
                // Auto-provision an inventory record for this product
                $item = $this->inventoryRepository->create([
                    'tenant_id'          => $tenantId,
                    'product_id'         => $productId,
                    'quantity_on_hand'   => max(0, $delta),
                    'quantity_reserved'  => 0,
                    'quantity_available' => max(0, $delta),
                ]);
            } else {
                $newOnHand   = $item->quantity_on_hand + $delta;
                $newAvail    = $newOnHand - $item->quantity_reserved;

                $this->inventoryRepository->update($item->id, [
                    'quantity_on_hand'   => max(0, $newOnHand),
                    'quantity_available' => max(0, $newAvail),
                ]);

                $item->refresh();
            }

            $this->eventPublisher->publish('kvsaas.events', 'inventory.adjusted', [
                'product_id' => $productId,
                'tenant_id'  => $tenantId,
                'delta'      => $delta,
                'reason'     => $reason,
                'on_hand'    => $item->quantity_on_hand,
                'available'  => $item->quantity_available,
            ]);

            return $item;
        });
    }

    /**
     * Reserve stock for an order (called by Order Saga via API).
     *
     * @param  string                            $orderId
     * @param  string                            $tenantId
     * @param  array<int, array<string, mixed>>  $items   [{product_id, quantity}]
     * @param  string|null                       $sagaId
     * @return InventoryReservation
     *
     * @throws \App\Domain\Inventory\Exceptions\InsufficientStockException
     */
    public function reserve(
        string  $orderId,
        string  $tenantId,
        array   $items,
        ?string $sagaId = null
    ): InventoryReservation {
        return DB::transaction(function () use ($orderId, $tenantId, $items, $sagaId): InventoryReservation {
            // Verify stock availability for ALL items before reserving any
            foreach ($items as $item) {
                $stock = $this->inventoryRepository->findByProductAndTenant(
                    $item['product_id'],
                    $tenantId
                );

                if ($stock === null || $stock->quantity_available < $item['quantity']) {
                    throw new \App\Domain\Inventory\Exceptions\InsufficientStockException(
                        $item['product_id'],
                        $item['quantity'],
                        $stock?->quantity_available ?? 0
                    );
                }
            }

            // Reserve each item
            foreach ($items as $item) {
                $stock = $this->inventoryRepository->findByProductAndTenant(
                    $item['product_id'],
                    $tenantId
                );

                $this->inventoryRepository->update($stock->id, [
                    'quantity_reserved'  => $stock->quantity_reserved  + $item['quantity'],
                    'quantity_available' => $stock->quantity_available  - $item['quantity'],
                ]);
            }

            // Create reservation record
            $reservation = InventoryReservation::create([
                'tenant_id'   => $tenantId,
                'order_id'    => $orderId,
                'saga_id'     => $sagaId,
                'status'      => 'active',
                'items'       => $items,
                'reserved_at' => now(),
                'expires_at'  => now()->addHours(24),
            ]);

            $this->eventPublisher->publish('kvsaas.events', 'inventory.reserved', [
                'reservation_id' => $reservation->id,
                'order_id'       => $orderId,
                'tenant_id'      => $tenantId,
            ]);

            Log::info("Inventory reserved", ['reservation_id' => $reservation->id]);

            return $reservation;
        });
    }

    /**
     * Release a reservation (Saga compensation step).
     *
     * @param  string $reservationId
     * @return void
     */
    public function releaseReservation(string $reservationId): void
    {
        DB::transaction(function () use ($reservationId): void {
            $reservation = InventoryReservation::findOrFail($reservationId);

            if ($reservation->status === 'released') {
                return; // Idempotent
            }

            foreach ($reservation->items as $item) {
                $stock = $this->inventoryRepository->findByProductAndTenant(
                    $item['product_id'],
                    $reservation->tenant_id
                );

                if ($stock) {
                    $this->inventoryRepository->update($stock->id, [
                        'quantity_reserved'  => max(0, $stock->quantity_reserved  - $item['quantity']),
                        'quantity_available' => $stock->quantity_available + $item['quantity'],
                    ]);
                }
            }

            $reservation->update([
                'status'      => 'released',
                'released_at' => now(),
            ]);

            $this->eventPublisher->publish('kvsaas.events', 'inventory.reservation_released', [
                'reservation_id' => $reservationId,
                'tenant_id'      => $reservation->tenant_id,
            ]);

            Log::info("Reservation released", ['reservation_id' => $reservationId]);
        });
    }
}
