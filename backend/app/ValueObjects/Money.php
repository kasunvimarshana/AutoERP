<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Money Value Object
 * 
 * Immutable representation of a monetary amount
 */
final class Money implements JsonSerializable, Stringable
{
    private function __construct(
        private readonly float $amount,
        private readonly string $currency
    ) {
        if ($this->amount < 0) {
            throw new InvalidArgumentException("Amount cannot be negative");
        }

        if (empty($this->currency) || strlen($this->currency) !== 3) {
            throw new InvalidArgumentException("Currency must be a 3-letter ISO code");
        }
    }

    public static function fromAmount(float $amount, string $currency = 'USD'): self
    {
        return new self($amount, strtoupper($currency));
    }

    public static function zero(string $currency = 'USD'): self
    {
        return new self(0.0, strtoupper($currency));
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        $newAmount = $this->amount - $other->amount;
        
        if ($newAmount < 0) {
            throw new InvalidArgumentException("Cannot subtract more than current amount");
        }
        
        return new self($newAmount, $this->currency);
    }

    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException("Multiplier cannot be negative");
        }
        
        return new self($this->amount * $multiplier, $this->currency);
    }

    public function divide(float $divisor): self
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException("Divisor must be positive");
        }
        
        return new self($this->amount / $divisor, $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function isZero(): bool
    {
        return $this->amount === 0.0;
    }

    public function format(): string
    {
        return sprintf('%s %.2f', $this->currency, $this->amount);
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currency} and {$other->currency}"
            );
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->format(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
