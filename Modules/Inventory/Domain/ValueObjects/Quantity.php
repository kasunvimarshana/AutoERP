<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

final class Quantity
{
    private const SCALE = 4;

    private readonly string $amount;

    public function __construct(string $amount)
    {
        if (! is_numeric($amount)) {
            throw new \InvalidArgumentException("Invalid quantity: \"{$amount}\".");
        }

        $this->amount = bcadd($amount, '0', self::SCALE);
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function add(Quantity $other): self
    {
        return new self(bcadd($this->amount, $other->amount, self::SCALE));
    }

    public function subtract(Quantity $other): self
    {
        return new self(bcsub($this->amount, $other->amount, self::SCALE));
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) < 0;
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) === 0;
    }

    public function isGreaterThan(Quantity $other): bool
    {
        return bccomp($this->amount, $other->amount, self::SCALE) > 0;
    }

    public function isGreaterThanOrEqual(Quantity $other): bool
    {
        return bccomp($this->amount, $other->amount, self::SCALE) >= 0;
    }

    public function __toString(): string
    {
        return $this->amount;
    }
}
