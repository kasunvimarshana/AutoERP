<?php

namespace App\Services;

use App\Domain\Aggregates\InventoryAggregate;
use App\Domain\Contracts\StockRepositoryInterface;
use App\Domain\Contracts\StockMovementRepositoryInterface;
use App\Domain\Contracts\StockServiceInterface;
use App\Domain\Events\StockReservationExpired;
use App\Domain\Models\StockReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StockService implements StockServiceInterface
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly StockMovementRepositoryInterface $movementRepository,
        private readonly EventPublisherService $eventPublisher,
    ) {
    }

    // -------------------------------------------------------------------------
    // Get Stock Level
    // -------------------------------------------------------------------------

    public function getStockLevel(string $tenantId, string $productId, string $warehouseId): object
    {
        return $this->stockRepository->getOrCreateStockLevel($tenantId, $productId, $warehouseId);
    }

    // -------------------------------------------------------------------------
    // Adjust Stock (atomic)
    // -------------------------------------------------------------------------

    public function adjustStock(
        string $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        string $type,
        ?string $referenceId   = null,
        ?string $referenceType = null,
        ?string $notes         = null,
        ?string $performedBy   = null,
    ): array {
        return DB::transaction(function () use (
            $tenantId, $productId, $warehouseId, $quantity, $type,
            $referenceId, $referenceType, $notes, $performedBy
        ) {
            $stockLevel = $this->stockRepository->lockStockLevelForUpdate($tenantId, $productId, $warehouseId)
                ?? $this->stockRepository->getOrCreateStockLevel($tenantId, $productId, $warehouseId);

            $stockLevel->load('product');

            $aggregate = InventoryAggregate::fromStockLevel($stockLevel);

            $movement = $aggregate->adjust($quantity, $type, [
                'reference_id'   => $referenceId,
                'reference_type' => $referenceType,
                'notes'          => $notes,
                'performed_by'   => $performedBy,
            ]);

            $updatedStockLevel = $aggregate->getStockLevel();
            $updatedStockLevel->save();

            $movement->save();

            foreach ($aggregate->pullDomainEvents() as $event) {
                $this->eventPublisher->publish($event);
            }

            return [
                'stock_level' => $updatedStockLevel->fresh(['product', 'warehouse']),
                'movement'    => $movement->fresh(['product', 'warehouse']),
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Reserve Stock
    // -------------------------------------------------------------------------

    public function reserveStock(
        string $tenantId,
        string $productId,
        string $warehouseId,
        int $quantity,
        string $referenceId,
        string $referenceType,
        ?\DateTime $expiresAt  = null,
        ?string $notes         = null,
    ): object {
        return DB::transaction(function () use (
            $tenantId, $productId, $warehouseId, $quantity,
            $referenceId, $referenceType, $expiresAt, $notes
        ) {
            $stockLevel = $this->stockRepository->lockStockLevelForUpdate($tenantId, $productId, $warehouseId)
                ?? $this->stockRepository->getOrCreateStockLevel($tenantId, $productId, $warehouseId);

            $stockLevel->load('product');

            $aggregate = InventoryAggregate::fromStockLevel($stockLevel);

            $reservation = $aggregate->reserve($quantity, [
                'reference_id'   => $referenceId,
                'reference_type' => $referenceType,
                'notes'          => $notes,
                'expires_at'     => $expiresAt ?? now()->addSeconds(config('inventory.reservation_ttl', 3600)),
            ]);

            $updatedStockLevel = $aggregate->getStockLevel();
            $updatedStockLevel->save();

            $reservation->save();

            foreach ($aggregate->pullDomainEvents() as $event) {
                if ($event instanceof \App\Domain\Events\StockReserved) {
                    $eventWithId = new \App\Domain\Events\StockReserved(
                        productId:     $event->productId,
                        warehouseId:   $event->warehouseId,
                        quantity:      $event->quantity,
                        referenceId:   $event->referenceId,
                        referenceType: $event->referenceType,
                        tenantId:      $event->tenantId,
                        reservationId: $reservation->id,
                        expiresAt:     $reservation->expires_at,
                    );
                    $this->eventPublisher->publish($eventWithId);
                } else {
                    $this->eventPublisher->publish($event);
                }
            }

            return $reservation;
        });
    }

    // -------------------------------------------------------------------------
    // Commit Reservation
    // -------------------------------------------------------------------------

    public function commitReservation(string $tenantId, string $reservationId, ?string $performedBy = null): array
    {
        return DB::transaction(function () use ($tenantId, $reservationId, $performedBy) {
            $reservation = $this->stockRepository->findReservationById($tenantId, $reservationId);

            if (!$reservation) {
                throw new RuntimeException("Reservation not found: {$reservationId}");
            }

            if (!$reservation->canBeCommitted()) {
                throw new RuntimeException(
                    "Reservation '{$reservationId}' cannot be committed. Status: {$reservation->status}."
                );
            }

            $stockLevel = $this->stockRepository->lockStockLevelForUpdate(
                $tenantId, $reservation->product_id, $reservation->warehouse_id
            );

            if (!$stockLevel) {
                throw new RuntimeException("Stock level not found for reservation.");
            }

            // Commit: reserved qty moves from on_hand (no change to on_hand as it was already decremented)
            $stockLevel->quantity_reserved = max(0, (float) $stockLevel->quantity_reserved - $reservation->quantity);
            $stockLevel->quantity_on_hand  = max(0, (float) $stockLevel->quantity_on_hand  - $reservation->quantity);
            $stockLevel->incrementVersion();
            $stockLevel->save();

            $movement = $this->movementRepository->create([
                'tenant_id'       => $tenantId,
                'product_id'      => $reservation->product_id,
                'warehouse_id'    => $reservation->warehouse_id,
                'type'            => 'commit',
                'quantity'        => $reservation->quantity,
                'before_quantity' => (float) $stockLevel->quantity_on_hand + $reservation->quantity,
                'after_quantity'  => (float) $stockLevel->quantity_on_hand,
                'reference_id'    => $reservationId,
                'reference_type'  => 'reservation',
                'performed_by'    => $performedBy,
            ]);

            $reservation->status       = StockReservation::STATUS_COMMITTED;
            $reservation->committed_at = now();
            $reservation->committed_by = $performedBy;
            $reservation->save();

            return [
                'stock_level' => $stockLevel->fresh(['product', 'warehouse']),
                'movement'    => $movement->fresh(['product', 'warehouse']),
                'reservation' => $reservation,
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Release Reservation
    // -------------------------------------------------------------------------

    public function releaseReservation(string $tenantId, string $reservationId, ?string $performedBy = null): array
    {
        return DB::transaction(function () use ($tenantId, $reservationId, $performedBy) {
            $reservation = $this->stockRepository->findReservationById($tenantId, $reservationId);

            if (!$reservation) {
                throw new RuntimeException("Reservation not found: {$reservationId}");
            }

            if (!$reservation->canBeReleased()) {
                throw new RuntimeException(
                    "Reservation '{$reservationId}' cannot be released. Status: {$reservation->status}."
                );
            }

            $stockLevel = $this->stockRepository->lockStockLevelForUpdate(
                $tenantId, $reservation->product_id, $reservation->warehouse_id
            );

            if (!$stockLevel) {
                throw new RuntimeException("Stock level not found for reservation.");
            }

            // Return qty from reserved back to available
            $stockLevel->quantity_available += $reservation->quantity;
            $stockLevel->quantity_reserved   = max(0, (float) $stockLevel->quantity_reserved - $reservation->quantity);
            $stockLevel->incrementVersion();
            $stockLevel->save();

            $reservation->status      = StockReservation::STATUS_RELEASED;
            $reservation->released_at = now();
            $reservation->released_by = $performedBy;
            $reservation->save();

            return [
                'stock_level' => $stockLevel->fresh(['product', 'warehouse']),
                'reservation' => $reservation,
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Transfer Stock (atomic two-leg)
    // -------------------------------------------------------------------------

    public function transferStock(
        string $tenantId,
        string $productId,
        string $fromWarehouseId,
        string $toWarehouseId,
        float $quantity,
        ?string $notes       = null,
        ?string $performedBy = null,
    ): array {
        return DB::transaction(function () use (
            $tenantId, $productId, $fromWarehouseId, $toWarehouseId, $quantity, $notes, $performedBy
        ) {
            // Lock both stock levels in a deterministic order to prevent deadlocks
            $ids = [$fromWarehouseId, $toWarehouseId];
            sort($ids);

            $fromStockLevel = $this->stockRepository->lockStockLevelForUpdate($tenantId, $productId, $fromWarehouseId)
                ?? $this->stockRepository->getOrCreateStockLevel($tenantId, $productId, $fromWarehouseId);

            $toStockLevel = $this->stockRepository->lockStockLevelForUpdate($tenantId, $productId, $toWarehouseId)
                ?? $this->stockRepository->getOrCreateStockLevel($tenantId, $productId, $toWarehouseId);

            $fromStockLevel->load('product');
            $toStockLevel->load('product');

            // Validate source has sufficient stock
            if ((float) $fromStockLevel->quantity_available < $quantity) {
                throw new RuntimeException(
                    "Insufficient available stock in warehouse '{$fromWarehouseId}'. Available: {$fromStockLevel->quantity_available}, Requested: {$quantity}."
                );
            }

            $transferRef = 'transfer_' . \Illuminate\Support\Str::uuid();

            // Outgoing
            $fromAggregate = InventoryAggregate::fromStockLevel($fromStockLevel);
            $outMovement   = $fromAggregate->adjust(-$quantity, 'transfer_out', [
                'reference_id'   => $transferRef,
                'reference_type' => 'transfer',
                'notes'          => $notes,
                'performed_by'   => $performedBy,
            ]);
            $fromAggregate->getStockLevel()->save();
            $outMovement->save();

            // Incoming
            $toAggregate = InventoryAggregate::fromStockLevel($toStockLevel);
            $inMovement  = $toAggregate->adjust($quantity, 'transfer_in', [
                'reference_id'   => $transferRef,
                'reference_type' => 'transfer',
                'notes'          => $notes,
                'performed_by'   => $performedBy,
            ]);
            $toAggregate->getStockLevel()->save();
            $inMovement->save();

            foreach (array_merge($fromAggregate->pullDomainEvents(), $toAggregate->pullDomainEvents()) as $event) {
                $this->eventPublisher->publish($event);
            }

            return [
                'from_level'    => $fromAggregate->getStockLevel()->fresh(['product', 'warehouse']),
                'to_level'      => $toAggregate->getStockLevel()->fresh(['product', 'warehouse']),
                'out_movement'  => $outMovement->fresh(['product', 'warehouse']),
                'in_movement'   => $inMovement->fresh(['product', 'warehouse']),
                'transfer_ref'  => $transferRef,
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Expire Reservations (scheduled cleanup)
    // -------------------------------------------------------------------------

    public function expireReservations(): int
    {
        $expired = $this->stockRepository->getExpiredReservations();
        $count   = 0;

        foreach ($expired as $reservation) {
            try {
                DB::transaction(function () use ($reservation, &$count) {
                    $stockLevel = $this->stockRepository->lockStockLevelForUpdate(
                        $reservation->tenant_id,
                        $reservation->product_id,
                        $reservation->warehouse_id
                    );

                    if ($stockLevel) {
                        $stockLevel->quantity_available += $reservation->quantity;
                        $stockLevel->quantity_reserved   = max(0, (float) $stockLevel->quantity_reserved - $reservation->quantity);
                        $stockLevel->incrementVersion();
                        $stockLevel->save();
                    }

                    $reservation->status = StockReservation::STATUS_EXPIRED;
                    $reservation->save();

                    $this->eventPublisher->publish(new StockReservationExpired(
                        reservationId: $reservation->id,
                        productId:     $reservation->product_id,
                        warehouseId:   $reservation->warehouse_id,
                        quantity:      $reservation->quantity,
                        referenceId:   $reservation->reference_id,
                        referenceType: $reservation->reference_type,
                        tenantId:      $reservation->tenant_id,
                    ));

                    $count++;
                });
            } catch (\Throwable $e) {
                Log::error("Failed to expire reservation {$reservation->id}: " . $e->getMessage());
            }
        }

        return $count;
    }
}
