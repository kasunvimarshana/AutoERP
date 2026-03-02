<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

final class Price
{
    private const SCALE = 4;

    private function __construct(
        private readonly string $amount,
        private readonly string $currency,
    ) {}

    public static function of(string $amount, string $currency): self
    {
        if (! is_numeric($amount)) {
            throw new \InvalidArgumentException("Invalid price amount: \"{$amount}\".");
        }

        if (bccomp($amount, '0', self::SCALE) < 0) {
            throw new \InvalidArgumentException('Price amount cannot be negative.');
        }

        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency code must be exactly 3 characters (ISO 4217).');
        }

        return new self(
            amount: bcadd($amount, '0', self::SCALE),
            currency: strtoupper($currency),
        );
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Price $other): self
    {
        $this->assertSameCurrency($other);

        return new self(
            amount: bcadd($this->amount, $other->amount, self::SCALE),
            currency: $this->currency,
        );
    }

    public function multiply(string $factor): self
    {
        return new self(
            amount: bcmul($this->amount, $factor, self::SCALE),
            currency: $this->currency,
        );
    }

    public function isGreaterThan(Price $other): bool
    {
        $this->assertSameCurrency($other);

        return bccomp($this->amount, $other->amount, self::SCALE) > 0;
    }

    public function isEqualTo(Price $other): bool
    {
        $this->assertSameCurrency($other);

        return bccomp($this->amount, $other->amount, self::SCALE) === 0;
    }

    private function assertSameCurrency(Price $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                "Cannot operate on prices with different currencies: {$this->currency} vs {$other->currency}."
            );
        }
    }

    public function __toString(): string
    {
        return "{$this->currency} {$this->amount}";
    }
}
