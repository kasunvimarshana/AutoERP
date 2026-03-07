<?php

declare(strict_types=1);

namespace App\Domain\Inventory\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a stock level with business constraints.
 */
final class StockLevel
{
    public readonly int $quantity;
    public readonly int $reservedQuantity;
    public readonly int $reorderPoint;

    public function __construct(
        int $quantity,
        int $reservedQuantity = 0,
        int $reorderPoint = 0
    ) {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Stock quantity cannot be negative.');
        }
        if ($reservedQuantity < 0) {
            throw new InvalidArgumentException('Reserved quantity cannot be negative.');
        }
        if ($reservedQuantity > $quantity) {
            throw new InvalidArgumentException(
                'Reserved quantity cannot exceed total stock quantity.'
            );
        }
        if ($reorderPoint < 0) {
            throw new InvalidArgumentException('Reorder point cannot be negative.');
        }

        $this->quantity         = $quantity;
        $this->reservedQuantity = $reservedQuantity;
        $this->reorderPoint     = $reorderPoint;
    }

    /** Available (uncommitted) stock. */
    public function available(): int
    {
        return $this->quantity - $this->reservedQuantity;
    }

    /** Whether this level is at or below the reorder point. */
    public function isLow(): bool
    {
        return $this->quantity <= $this->reorderPoint;
    }

    /** Whether there is no stock at all. */
    public function isOutOfStock(): bool
    {
        return $this->quantity === 0;
    }

    /** Whether the given quantity can be fulfilled from available stock. */
    public function canFulfil(int $quantity): bool
    {
        return $this->available() >= $quantity;
    }

    /**
     * Return a new StockLevel after adding the given quantity (receipt / adjustment).
     */
    public function add(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Use subtract() to reduce stock.');
        }

        return new self(
            $this->quantity + $quantity,
            $this->reservedQuantity,
            $this->reorderPoint
        );
    }

    /**
     * Return a new StockLevel after subtracting the given quantity.
     *
     * @throws \UnderflowException When the result would be negative.
     */
    public function subtract(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity to subtract must be non-negative.');
        }

        if ($this->quantity - $quantity < 0) {
            throw new \UnderflowException(
                "Cannot subtract {$quantity} from stock of {$this->quantity}."
            );
        }

        return new self(
            $this->quantity - $quantity,
            $this->reservedQuantity,
            $this->reorderPoint
        );
    }

    /**
     * Return a new StockLevel with an updated reservation.
     */
    public function withReservation(int $reservedQuantity): self
    {
        return new self($this->quantity, $reservedQuantity, $this->reorderPoint);
    }

    /** Value-object equality. */
    public function equals(self $other): bool
    {
        return $this->quantity === $other->quantity
            && $this->reservedQuantity === $other->reservedQuantity
            && $this->reorderPoint === $other->reorderPoint;
    }

    public function toArray(): array
    {
        return [
            'quantity'          => $this->quantity,
            'reserved_quantity' => $this->reservedQuantity,
            'available'         => $this->available(),
            'reorder_point'     => $this->reorderPoint,
            'is_low'            => $this->isLow(),
            'is_out_of_stock'   => $this->isOutOfStock(),
        ];
    }

    public function __toString(): string
    {
        return (string) $this->quantity;
    }
}
