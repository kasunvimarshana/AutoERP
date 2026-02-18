<?php

declare(strict_types=1);

namespace Modules\Inventory\ValueObjects;

use JsonSerializable;
use Stringable;

/**
 * Stock Quantity Value Object
 *
 * Represents a quantity of stock with its unit of measure.
 * Ensures precision and consistency in stock calculations.
 *
 * @package Modules\Inventory\ValueObjects
 */
final class StockQuantity implements JsonSerializable, Stringable
{
    private const PRECISION_SCALE = 6; // 6 decimal places for stock precision

    private readonly string $quantity;
    private readonly string $unitOfMeasure;

    private function __construct(string|int|float $quantity, string $uom)
    {
        if (bccomp((string)$quantity, '0', self::PRECISION_SCALE) < 0) {
            throw new \InvalidArgumentException('Stock quantity cannot be negative');
        }

        $this->quantity = bcadd((string)$quantity, '0', self::PRECISION_SCALE);
        $this->unitOfMeasure = strtoupper($uom);
    }

    public static function of(string|int|float $quantity, string $uom): self
    {
        return new self($quantity, $uom);
    }

    public static function zero(string $uom = 'EA'): self
    {
        return new self('0', $uom);
    }

    public function add(StockQuantity $other): self
    {
        $this->ensureSameUnit($other);
        
        $newQuantity = bcadd($this->quantity, $other->quantity, self::PRECISION_SCALE);
        return new self($newQuantity, $this->unitOfMeasure);
    }

    public function subtract(StockQuantity $other): self
    {
        $this->ensureSameUnit($other);
        
        $newQuantity = bcsub($this->quantity, $other->quantity, self::PRECISION_SCALE);
        
        if (bccomp($newQuantity, '0', self::PRECISION_SCALE) < 0) {
            throw new \RuntimeException('Resulting stock quantity would be negative');
        }
        
        return new self($newQuantity, $this->unitOfMeasure);
    }

    public function multiplyBy(string|int|float $factor): self
    {
        $newQuantity = bcmul($this->quantity, (string)$factor, self::PRECISION_SCALE);
        return new self($newQuantity, $this->unitOfMeasure);
    }

    public function divideBy(string|int|float $divisor): self
    {
        if (bccomp((string)$divisor, '0', self::PRECISION_SCALE) === 0) {
            throw new \InvalidArgumentException('Cannot divide by zero');
        }
        
        $newQuantity = bcdiv($this->quantity, (string)$divisor, self::PRECISION_SCALE);
        return new self($newQuantity, $this->unitOfMeasure);
    }

    public function isGreaterThan(StockQuantity $other): bool
    {
        $this->ensureSameUnit($other);
        return bccomp($this->quantity, $other->quantity, self::PRECISION_SCALE) > 0;
    }

    public function isLessThan(StockQuantity $other): bool
    {
        $this->ensureSameUnit($other);
        return bccomp($this->quantity, $other->quantity, self::PRECISION_SCALE) < 0;
    }

    public function isGreaterThanOrEqual(StockQuantity $other): bool
    {
        $this->ensureSameUnit($other);
        return bccomp($this->quantity, $other->quantity, self::PRECISION_SCALE) >= 0;
    }

    public function isLessThanOrEqual(StockQuantity $other): bool
    {
        $this->ensureSameUnit($other);
        return bccomp($this->quantity, $other->quantity, self::PRECISION_SCALE) <= 0;
    }

    public function equals(StockQuantity $other): bool
    {
        return $this->unitOfMeasure === $other->unitOfMeasure
            && bccomp($this->quantity, $other->quantity, self::PRECISION_SCALE) === 0;
    }

    public function isZero(): bool
    {
        return bccomp($this->quantity, '0', self::PRECISION_SCALE) === 0;
    }

    public function isPositive(): bool
    {
        return bccomp($this->quantity, '0', self::PRECISION_SCALE) > 0;
    }

    public function isSufficientFor(StockQuantity $required): bool
    {
        return $this->isGreaterThanOrEqual($required);
    }

    public function toFloat(): float
    {
        return (float)$this->quantity;
    }

    public function toString(): string
    {
        // Check if zero first to avoid edge cases
        if (bccomp($this->quantity, '0', self::PRECISION_SCALE) === 0) {
            return '0';
        }
        
        $trimmed = rtrim(rtrim($this->quantity, '0'), '.');
        return $trimmed === '' ? '0' : $trimmed;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getUnitOfMeasure(): string
    {
        return $this->unitOfMeasure;
    }

    public function __toString(): string
    {
        return sprintf('%s %s', $this->toString(), $this->unitOfMeasure);
    }

    public function jsonSerialize(): array
    {
        return [
            'quantity' => $this->toString(),
            'unit' => $this->unitOfMeasure,
        ];
    }

    private function ensureSameUnit(StockQuantity $other): void
    {
        if ($this->unitOfMeasure !== $other->unitOfMeasure) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unit of measure mismatch: cannot operate on %s and %s',
                    $this->unitOfMeasure,
                    $other->unitOfMeasure
                )
            );
        }
    }
}
