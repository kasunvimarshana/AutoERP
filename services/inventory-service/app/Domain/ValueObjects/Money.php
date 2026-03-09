<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Money
{
    private int $amountCents;
    private string $currency;

    private const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'INR'];

    public function __construct(int $amountCents, string $currency = 'USD')
    {
        $currency = strtoupper(trim($currency));

        if (!in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
            throw new InvalidArgumentException("Unsupported currency: '{$currency}'.");
        }

        if ($amountCents < 0) {
            throw new InvalidArgumentException("Money amount cannot be negative.");
        }

        $this->amountCents = $amountCents;
        $this->currency    = $currency;
    }

    public static function fromFloat(float $amount, string $currency = 'USD'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public static function fromString(string $amount, string $currency = 'USD'): self
    {
        return self::fromFloat((float) $amount, $currency);
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function getAmount(): float
    {
        return $this->amountCents / 100.0;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amountCents + $other->amountCents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        $result = $this->amountCents - $other->amountCents;
        if ($result < 0) {
            throw new InvalidArgumentException("Subtraction results in negative money amount.");
        }
        return new self($result, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->amountCents * $factor), $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amountCents === $other->amountCents
            && $this->currency    === $other->currency;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amountCents > $other->amountCents;
    }

    public function format(): string
    {
        return number_format($this->getAmount(), 2) . ' ' . $this->currency;
    }

    public function toArray(): array
    {
        return [
            'amount'        => $this->getAmount(),
            'amount_cents'  => $this->amountCents,
            'currency'      => $this->currency,
            'formatted'     => $this->format(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}."
            );
        }
    }
}
