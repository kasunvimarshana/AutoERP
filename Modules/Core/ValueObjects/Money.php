<?php

declare(strict_types=1);

namespace Modules\Core\ValueObjects;

use Modules\Core\Exceptions\ValidationException;

/**
 * Money Value Object
 *
 * Represents a monetary amount with currency
 * Immutable - once created, cannot be changed
 * Uses BCMath for precise calculations
 */
final class Money
{
    private readonly string $amount;

    private readonly Currency $currency;

    private const SCALE = 2; // Decimal precision

    /**
     * Create a new money value object
     *
     * @throws ValidationException
     */
    public function __construct(string|int|float $amount, Currency $currency)
    {
        // Convert to string for BCMath
        $amount = (string) $amount;

        // Validate amount is numeric
        if (! is_numeric($amount)) {
            throw ValidationException::forField('amount', 'Amount must be numeric');
        }

        // Round to currency precision
        $this->amount = bcadd($amount, '0', self::SCALE);
        $this->currency = $currency;
    }

    /**
     * Get the amount value
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Get the currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Add money
     *
     * @throws ValidationException
     */
    public function add(Money $other): static
    {
        $this->assertSameCurrency($other);

        $newAmount = bcadd($this->amount, $other->amount, self::SCALE);

        return new self($newAmount, $this->currency);
    }

    /**
     * Subtract money
     *
     * @throws ValidationException
     */
    public function subtract(Money $other): static
    {
        $this->assertSameCurrency($other);

        $newAmount = bcsub($this->amount, $other->amount, self::SCALE);

        return new static($newAmount, $this->currency);
    }

    /**
     * Multiply by a factor
     */
    public function multiply(string|int|float $multiplier): static
    {
        $multiplier = (string) $multiplier;
        $newAmount = bcmul($this->amount, $multiplier, self::SCALE);

        return new static($newAmount, $this->currency);
    }

    /**
     * Divide by a divisor
     *
     * @throws ValidationException
     */
    public function divide(string|int|float $divisor): static
    {
        $divisor = (string) $divisor;

        if (bccomp($divisor, '0', self::SCALE) === 0) {
            throw ValidationException::forField('divisor', 'Cannot divide by zero');
        }

        $newAmount = bcdiv($this->amount, $divisor, self::SCALE);

        return new static($newAmount, $this->currency);
    }

    /**
     * Check if positive
     */
    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) > 0;
    }

    /**
     * Check if negative
     */
    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) < 0;
    }

    /**
     * Check if zero
     */
    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) === 0;
    }

    /**
     * Check if equals another money
     */
    public function equals(Money $other): bool
    {
        return $this->currency->equals($other->currency)
            && bccomp($this->amount, $other->amount, self::SCALE) === 0;
    }

    /**
     * Check if greater than another money
     *
     * @throws ValidationException
     */
    public function greaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return bccomp($this->amount, $other->amount, self::SCALE) > 0;
    }

    /**
     * Check if less than another money
     *
     * @throws ValidationException
     */
    public function lessThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return bccomp($this->amount, $other->amount, self::SCALE) < 0;
    }

    /**
     * Assert same currency
     *
     * @throws ValidationException
     */
    private function assertSameCurrency(Money $other): void
    {
        if (! $this->currency->equals($other->currency)) {
            throw ValidationException::forField(
                'currency',
                "Cannot perform operation with different currencies: {$this->currency} and {$other->currency}"
            );
        }
    }

    /**
     * Format for display
     */
    public function format(): string
    {
        return $this->currency->getSymbol().' '.number_format((float) $this->amount, self::SCALE);
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Create from amount and currency code
     *
     * @throws ValidationException
     */
    public static function fromAmount(string|int|float $amount, string $currencyCode): static
    {
        return new static($amount, Currency::fromCode($currencyCode));
    }

    /**
     * Create zero money
     */
    public static function zero(Currency $currency): static
    {
        return new static('0', $currency);
    }

    /**
     * Serialize to array
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => (string) $this->currency,
            'formatted' => $this->format(),
        ];
    }
}
