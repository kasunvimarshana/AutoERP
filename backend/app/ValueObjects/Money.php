<?php

<<<<<<< HEAD
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
=======
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

>>>>>>> kv-erp-001
    public function getAmount(): float
    {
        return $this->amount;
    }

<<<<<<< HEAD
    /**
     * Get the currency code
     */
=======
>>>>>>> kv-erp-001
    public function getCurrency(): string
    {
        return $this->currency;
    }

<<<<<<< HEAD
    /**
     * Add another Money object
     * Both must be in the same currency
     */
=======
>>>>>>> kv-erp-001
    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

<<<<<<< HEAD
    /**
     * Subtract another Money object
     * Both must be in the same currency
     */
=======
>>>>>>> kv-erp-001
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);
        $newAmount = $this->amount - $other->amount;
        
        if ($newAmount < 0) {
<<<<<<< HEAD
            throw new InvalidArgumentException('Resulting amount cannot be negative');
=======
            throw new InvalidArgumentException("Cannot subtract more than current amount");
>>>>>>> kv-erp-001
        }
        
        return new self($newAmount, $this->currency);
    }

<<<<<<< HEAD
    /**
     * Multiply by a factor
     */
    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative');
=======
    public function multiply(float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException("Multiplier cannot be negative");
>>>>>>> kv-erp-001
        }
        
        return new self($this->amount * $multiplier, $this->currency);
    }

<<<<<<< HEAD
    /**
     * Divide by a divisor
     */
    public function divide(float $divisor): self
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException('Divisor must be positive');
=======
    public function divide(float $divisor): self
    {
        if ($divisor <= 0) {
            throw new InvalidArgumentException("Divisor must be positive");
>>>>>>> kv-erp-001
        }
        
        return new self($this->amount / $divisor, $this->currency);
    }

<<<<<<< HEAD
    /**
     * Check if this Money is greater than another
     */
    public function greaterThan(Money $other): bool
=======
    public function isGreaterThan(Money $other): bool
>>>>>>> kv-erp-001
    {
        $this->assertSameCurrency($other);
        return $this->amount > $other->amount;
    }

<<<<<<< HEAD
    /**
     * Check if this Money is less than another
     */
    public function lessThan(Money $other): bool
=======
    public function isLessThan(Money $other): bool
>>>>>>> kv-erp-001
    {
        $this->assertSameCurrency($other);
        return $this->amount < $other->amount;
    }

<<<<<<< HEAD
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
=======
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

>>>>>>> kv-erp-001
    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
<<<<<<< HEAD
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }
=======
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
>>>>>>> kv-erp-001
}
