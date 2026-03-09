<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Quantity
{
    private float $value;

    public function __construct(float $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException(
                "Quantity cannot be negative. Got: {$value}."
            );
        }

        $this->value = $value;
    }

    public static function of(float $value): self
    {
        return new self($value);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function isZero(): bool
    {
        return $this->value === 0.0;
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function subtract(self $other): self
    {
        $result = $this->value - $other->value;
        if ($result < 0) {
            throw new InvalidArgumentException(
                "Subtraction results in negative quantity ({$this->value} - {$other->value} = {$result})."
            );
        }
        return new self($result);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->value >= $other->value;
    }

    public function isLessThan(self $other): bool
    {
        return $this->value < $other->value;
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < PHP_FLOAT_EPSILON;
    }

    public function toInt(): int
    {
        return (int) round($this->value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
