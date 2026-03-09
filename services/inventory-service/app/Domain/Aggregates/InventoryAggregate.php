<?php

namespace App\Domain\Aggregates;

use App\Domain\Events\LowStockDetected;
use App\Domain\Events\StockAdjusted;
use App\Domain\Events\StockReserved;
use App\Domain\Models\StockLevel;
use App\Domain\Models\StockMovement;
use App\Domain\Models\StockReservation;
use App\Domain\ValueObjects\Quantity;
use RuntimeException;

/**
 * InventoryAggregate encapsulates all business rules for stock level management.
 * It validates operations, raises domain events, and ensures consistency.
 */
class InventoryAggregate
{
    private array $domainEvents = [];
    private StockLevel $stockLevel;

    public function __construct(StockLevel $stockLevel)
    {
        $this->stockLevel = $stockLevel;
    }

    public static function fromStockLevel(StockLevel $stockLevel): self
    {
        return new self($stockLevel);
    }

    // -------------------------------------------------------------------------
    // Stock Adjustment
    // -------------------------------------------------------------------------

    public function adjust(float $quantity, string $type, array $context = []): StockMovement
    {
        $this->validateMovementType($type);

        $beforeQty = (float) $this->stockLevel->quantity_on_hand;

        $newAvailable = match (true) {
            in_array($type, [StockMovement::TYPE_RECEIPT, StockMovement::TYPE_TRANSFER_IN])
                => (float) $this->stockLevel->quantity_available + $quantity,

            in_array($type, [StockMovement::TYPE_ISSUE, StockMovement::TYPE_TRANSFER_OUT, StockMovement::TYPE_COMMIT])
                => (float) $this->stockLevel->quantity_available - $quantity,

            $type === StockMovement::TYPE_ADJUSTMENT
                => (float) $this->stockLevel->quantity_available + $quantity,

            default => (float) $this->stockLevel->quantity_available,
        };

        $newOnHand = match (true) {
            in_array($type, [StockMovement::TYPE_RECEIPT, StockMovement::TYPE_TRANSFER_IN])
                => (float) $this->stockLevel->quantity_on_hand + $quantity,

            in_array($type, [StockMovement::TYPE_ISSUE, StockMovement::TYPE_TRANSFER_OUT])
                => (float) $this->stockLevel->quantity_on_hand - $quantity,

            $type === StockMovement::TYPE_ADJUSTMENT
                => (float) $this->stockLevel->quantity_on_hand + $quantity,

            default => (float) $this->stockLevel->quantity_on_hand,
        };

        if ($newAvailable < 0) {
            throw new RuntimeException(
                "Insufficient available stock. Available: {$this->stockLevel->quantity_available}, Requested: {$quantity}."
            );
        }

        if ($newOnHand < 0) {
            throw new RuntimeException(
                "Operation would result in negative on-hand quantity."
            );
        }

        $this->stockLevel->quantity_available = $newAvailable;
        $this->stockLevel->quantity_on_hand   = $newOnHand;
        $this->stockLevel->incrementVersion();

        $movement = new StockMovement([
            'tenant_id'       => $this->stockLevel->tenant_id,
            'product_id'      => $this->stockLevel->product_id,
            'warehouse_id'    => $this->stockLevel->warehouse_id,
            'type'            => $type,
            'quantity'        => abs($quantity),
            'before_quantity' => $beforeQty,
            'after_quantity'  => $newOnHand,
            'reference_id'    => $context['reference_id']    ?? null,
            'reference_type'  => $context['reference_type']  ?? null,
            'notes'           => $context['notes']            ?? null,
            'metadata'        => $context['metadata']         ?? null,
            'performed_by'    => $context['performed_by']     ?? null,
        ]);

        $this->domainEvents[] = new StockAdjusted(
            productId:    $this->stockLevel->product_id,
            warehouseId:  $this->stockLevel->warehouse_id,
            oldQty:       $beforeQty,
            newQty:       $newOnHand,
            movementType: $type,
            tenantId:     $this->stockLevel->tenant_id,
        );

        $this->checkLowStock($newAvailable);

        return $movement;
    }

    // -------------------------------------------------------------------------
    // Stock Reservation
    // -------------------------------------------------------------------------

    public function reserve(int $quantity, array $context = []): StockReservation
    {
        $available = (float) $this->stockLevel->quantity_available;

        if ($available < $quantity) {
            throw new RuntimeException(
                "Insufficient stock to reserve. Available: {$available}, Requested: {$quantity}."
            );
        }

        $this->stockLevel->quantity_available -= $quantity;
        $this->stockLevel->quantity_reserved  = ((float) $this->stockLevel->quantity_reserved) + $quantity;
        $this->stockLevel->incrementVersion();

        $ttl = config('inventory.reservation_ttl', 3600);
        $expiresAt = $context['expires_at'] ?? now()->addSeconds($ttl);

        $reservation = new StockReservation([
            'tenant_id'      => $this->stockLevel->tenant_id,
            'product_id'     => $this->stockLevel->product_id,
            'warehouse_id'   => $this->stockLevel->warehouse_id,
            'quantity'       => $quantity,
            'reference_id'   => $context['reference_id']   ?? null,
            'reference_type' => $context['reference_type'] ?? null,
            'notes'          => $context['notes']           ?? null,
            'metadata'       => $context['metadata']        ?? null,
            'status'         => StockReservation::STATUS_PENDING,
            'expires_at'     => $expiresAt,
        ]);

        $this->domainEvents[] = new StockReserved(
            productId:    $this->stockLevel->product_id,
            warehouseId:  $this->stockLevel->warehouse_id,
            quantity:     $quantity,
            referenceId:  $context['reference_id']   ?? null,
            referenceType:$context['reference_type'] ?? null,
            tenantId:     $this->stockLevel->tenant_id,
        );

        $this->checkLowStock($this->stockLevel->quantity_available);

        return $reservation;
    }

    // -------------------------------------------------------------------------
    // State accessors
    // -------------------------------------------------------------------------

    public function getStockLevel(): StockLevel
    {
        return $this->stockLevel;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function validateMovementType(string $type): void
    {
        $valid = array_values(config('inventory.movement_types', [
            'receipt', 'issue', 'transfer_in', 'transfer_out',
            'adjustment', 'reservation', 'release', 'commit',
        ]));

        if (!in_array($type, $valid, true)) {
            throw new \InvalidArgumentException("Invalid movement type: '{$type}'.");
        }
    }

    private function checkLowStock(float $available): void
    {
        $product = $this->stockLevel->product;
        if (!$product) {
            return;
        }

        $reorderPoint = (int) ($product->reorder_point ?? config('inventory.low_stock_threshold', 10));

        if ($available <= $reorderPoint) {
            $this->domainEvents[] = new LowStockDetected(
                productId:    $this->stockLevel->product_id,
                warehouseId:  $this->stockLevel->warehouse_id,
                quantity:     $available,
                reorderPoint: $reorderPoint,
                tenantId:     $this->stockLevel->tenant_id,
            );
        }
    }
}
