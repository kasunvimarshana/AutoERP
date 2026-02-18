<?php

namespace Modules\Core\ValueObjects;

use JsonSerializable;
use Stringable;

/**
 * Represents a monetary amount with currency context for multi-tenant ERP
 * Handles precision, formatting, and currency conversions
 */
final class CurrencyAmount implements JsonSerializable, Stringable
{
    private const PRECISION_SCALE = 4; // 4 decimal places for financial accuracy
    
    private readonly string $amountInMinorUnits;
    private readonly string $currencyCode;
    private readonly int $decimalPlaces;
    
    private function __construct(string $amount, string $currency, int $decimals = 2)
    {
        $this->validateCurrencyCode($currency);
        $this->currencyCode = strtoupper($currency);
        $this->decimalPlaces = $decimals;
        
        // Store as string to avoid floating point issues
        $multiplier = bcpow('10', (string)$decimals, 0);
        $this->amountInMinorUnits = bcmul($amount, $multiplier, 0);
    }
    
    public static function fromMajor(string|int|float $amount, string $currency, int $decimals = 2): self
    {
        return new self((string)$amount, $currency, $decimals);
    }
    
    public static function fromMinor(string|int $minorUnits, string $currency, int $decimals = 2): self
    {
        $divisor = bcpow('10', (string)$decimals, 0);
        $majorAmount = bcdiv((string)$minorUnits, $divisor, self::PRECISION_SCALE);
        return new self($majorAmount, $currency, $decimals);
    }
    
    public static function zero(string $currency): self
    {
        return new self('0', $currency);
    }
    
    public function add(CurrencyAmount $other): self
    {
        $this->ensureSameCurrency($other);
        
        $newAmount = bcadd($this->amountInMinorUnits, $other->amountInMinorUnits, 0);
        return self::fromMinor($newAmount, $this->currencyCode, $this->decimalPlaces);
    }
    
    public function subtract(CurrencyAmount $other): self
    {
        $this->ensureSameCurrency($other);
        
        $newAmount = bcsub($this->amountInMinorUnits, $other->amountInMinorUnits, 0);
        return self::fromMinor($newAmount, $this->currencyCode, $this->decimalPlaces);
    }
    
    public function multiplyBy(string|int|float $factor): self
    {
        $newAmount = bcmul($this->amountInMinorUnits, (string)$factor, 0);
        return self::fromMinor($newAmount, $this->currencyCode, $this->decimalPlaces);
    }
    
    public function divideBy(string|int|float $divisor): self
    {
        if (bccomp((string)$divisor, '0', self::PRECISION_SCALE) === 0) {
            throw new \InvalidArgumentException('Cannot divide by zero');
        }
        
        $newAmount = bcdiv($this->amountInMinorUnits, (string)$divisor, 0);
        return self::fromMinor($newAmount, $this->currencyCode, $this->decimalPlaces);
    }
    
    public function isGreaterThan(CurrencyAmount $other): bool
    {
        $this->ensureSameCurrency($other);
        return bccomp($this->amountInMinorUnits, $other->amountInMinorUnits, 0) > 0;
    }
    
    public function isLessThan(CurrencyAmount $other): bool
    {
        $this->ensureSameCurrency($other);
        return bccomp($this->amountInMinorUnits, $other->amountInMinorUnits, 0) < 0;
    }
    
    public function equals(CurrencyAmount $other): bool
    {
        return $this->currencyCode === $other->currencyCode 
            && bccomp($this->amountInMinorUnits, $other->amountInMinorUnits, 0) === 0;
    }
    
    public function isPositive(): bool
    {
        return bccomp($this->amountInMinorUnits, '0', 0) > 0;
    }
    
    public function isNegative(): bool
    {
        return bccomp($this->amountInMinorUnits, '0', 0) < 0;
    }
    
    public function isZero(): bool
    {
        return bccomp($this->amountInMinorUnits, '0', 0) === 0;
    }
    
    public function toMajorUnits(): string
    {
        $divisor = bcpow('10', (string)$this->decimalPlaces, 0);
        return bcdiv($this->amountInMinorUnits, $divisor, $this->decimalPlaces);
    }
    
    public function toMinorUnits(): string
    {
        return $this->amountInMinorUnits;
    }
    
    public function getCurrency(): string
    {
        return $this->currencyCode;
    }
    
    public function format(?string $locale = null): string
    {
        $formatter = new \NumberFormatter(
            $locale ?? 'en_US',
            \NumberFormatter::CURRENCY
        );
        
        return $formatter->formatCurrency(
            (float)$this->toMajorUnits(),
            $this->currencyCode
        );
    }
    
    public function __toString(): string
    {
        return sprintf('%s %s', $this->toMajorUnits(), $this->currencyCode);
    }
    
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->toMajorUnits(),
            'currency' => $this->currencyCode,
            'minorUnits' => $this->amountInMinorUnits,
        ];
    }
    
    private function ensureSameCurrency(CurrencyAmount $other): void
    {
        if ($this->currencyCode !== $other->currencyCode) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Currency mismatch: cannot operate on %s and %s',
                    $this->currencyCode,
                    $other->currencyCode
                )
            );
        }
    }
    
    private function validateCurrencyCode(string $code): void
    {
        if (!preg_match('/^[A-Z]{3}$/', strtoupper($code))) {
            throw new \InvalidArgumentException(
                sprintf('Invalid currency code: %s. Expected ISO 4217 format (e.g., USD, EUR)', $code)
            );
        }
    }
}
