<?php

namespace App\ValueObjects;

use App\Services\Finance\CurrencyService;
use InvalidArgumentException;

/**
 * Value Object representing monetary value
 * Immutable and always associated with a currency
 */
class Money
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency)
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    /**
     * Create Money from array
     */
    public static function fromArray(array $data): self
    {
        return new self($data['amount'] ?? 0, $data['currency'] ?? 'USD');
    }

    /**
     * Get the amount
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the currency code
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Add another Money object
     * Both must be in the same currency
     */
    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    /**
     * Subtract another Money object
     * Both must be in the same currency
     */
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        $newAmount = $this->amount - $other->amount;
        
        if ($newAmount < 0) {
            throw new InvalidArgumentException('Resulting amount cannot be negative');
        }
        
        return new self($newAmount, $this->currency);
    }

    /**
     * Multiply by a factor
     */
    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
        }
        
        return new self($this->amount * $multiplier, $this->currency);
    }

    /**
     * Divide by a divisor
     */
    public function divide(float $divisor): self
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException('Divisor must be positive');
        }
        
        return new self($this->amount / $divisor, $this->currency);
    }

    /**
     * Check if this Money is greater than another
     */
    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

    /**
     * Check if this Money is less than another
     */
    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

    /**
     * Check if this Money equals another
     */
    public function equals(Money $other): bool
    {
        return $this->currency === $other->currency 
            && abs($this->amount - $other->amount) < 0.01; // Allow small floating point differences
    }

    /**
     * Check if amount is zero
     */
    public function isZero(): bool
    {
        return abs($this->amount) < 0.01;
    }

    /**
     * Convert to another currency
     */
    public function convertTo(string $toCurrency, ?CurrencyService $currencyService = null): self
    {
        if ($this->currency === $toCurrency) {
            return $this;
        }

        $currencyService = $currencyService ?? app(CurrencyService::class);
        $convertedAmount = $currencyService->convert(
            $this->amount,
            $this->currency,
            $toCurrency
        );

        return new self($convertedAmount, $toCurrency);
    }

    /**
     * Format for display
     */
    public function format(?CurrencyService $currencyService = null): string
    {
        $currencyService = $currencyService ?? app(CurrencyService::class);
        return $currencyService->formatAmount($this->amount, $this->currency);
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Assert that two Money objects have the same currency
     */
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
