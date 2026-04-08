<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

use InvalidArgumentException;

// ═══════════════════════════════════════════════════════════════════
// Money  — immutable currency-aware amount
// ═══════════════════════════════════════════════════════════════════
final class Money
{
    public function __construct(
        private readonly float  $amount,
        private readonly string $currency = 'USD',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException("Money amount cannot be negative: {$amount}");
        }
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException("Currency must be ISO 4217 (3 chars): {$currency}");
        }
    }

    public function amount(): float   { return $this->amount; }
    public function currency(): string { return strtoupper($this->currency); }

    public function add(self $other): self
    {
        $this->guardSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self(round($this->amount * $factor, 6), $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency() === $other->currency();
    }

    public function toArray(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency];
    }

    public static function fromArray(array $data): self
    {
        return new self((float) $data['amount'], $data['currency'] ?? 'USD');
    }

    public function __toString(): string
    {
        return "{$this->currency} {$this->amount}";
    }

    private function guardSameCurrency(self $other): void
    {
        if ($this->currency() !== $other->currency()) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currency()} vs {$other->currency()}"
            );
        }
    }
}


// ═══════════════════════════════════════════════════════════════════
// Sku  — validated Stock Keeping Unit identifier
// ═══════════════════════════════════════════════════════════════════
final class Sku
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '' || strlen($trimmed) > 100) {
            throw new InvalidArgumentException("SKU must be 1–100 characters: '{$value}'");
        }
        if (!preg_match('/^[A-Za-z0-9\-_\.]+$/', $trimmed)) {
            throw new InvalidArgumentException("SKU must be alphanumeric with - _ . only: '{$value}'");
        }
        $this->value = strtoupper($trimmed);
    }

    public function value(): string { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return $this->value; }
}


// ═══════════════════════════════════════════════════════════════════
// TenantId  — typed tenant identifier for multi-tenant isolation
// ═══════════════════════════════════════════════════════════════════
final class TenantId
{
    public function __construct(
        private readonly int $value,
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException("TenantId must be a positive integer: {$value}");
        }
    }

    public function value(): int { return $this->value; }
    public function equals(self $other): bool { return $this->value === $other->value; }
    public function __toString(): string { return (string) $this->value; }
}


// ═══════════════════════════════════════════════════════════════════
// Quantity  — typed, unit-aware quantity with decimal precision
// ═══════════════════════════════════════════════════════════════════
final class Quantity
{
    public function __construct(
        private readonly float  $value,
        private readonly string $unit    = 'pcs',
        private readonly int    $decimals = 4,
    ) {}

    public function value(): float  { return round($this->value, $this->decimals); }
    public function unit(): string  { return $this->unit; }

    public function add(self $other): self
    {
        $this->guardSameUnit($other);
        return new self($this->value + $other->value, $this->unit, $this->decimals);
    }

    public function subtract(self $other): self
    {
        $this->guardSameUnit($other);
        return new self($this->value - $other->value, $this->unit, $this->decimals);
    }

    public function isNegative(): bool { return $this->value < 0; }
    public function isZero(): bool     { return $this->value == 0; }
    public function equals(self $other): bool { return $this->value() === $other->value() && $this->unit === $other->unit; }

    public function toArray(): array
    {
        return ['value' => $this->value(), 'unit' => $this->unit];
    }

    public function __toString(): string { return "{$this->value()} {$this->unit}"; }

    private function guardSameUnit(self $other): void
    {
        if ($this->unit !== $other->unit) {
            throw new InvalidArgumentException(
                "Cannot operate on different units: {$this->unit} vs {$other->unit}"
            );
        }
    }
}


// ═══════════════════════════════════════════════════════════════════
// Percentage  — typed percentage for rates, discounts, margins
// ═══════════════════════════════════════════════════════════════════
final class Percentage
{
    public function __construct(private readonly float $value)
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException("Percentage must be between 0 and 100: {$value}");
        }
    }

    public function value(): float        { return $this->value; }
    public function asDecimal(): float    { return $this->value / 100; }
    public function __toString(): string  { return "{$this->value}%"; }
}


// ═══════════════════════════════════════════════════════════════════
// DateRange  — inclusive date range value object
// ═══════════════════════════════════════════════════════════════════
final class DateRange
{
    public function __construct(
        private readonly \DateTimeInterface $from,
        private readonly \DateTimeInterface $to,
    ) {
        if ($from > $to) {
            throw new InvalidArgumentException('DateRange: from must be before or equal to to');
        }
    }

    public function from(): \DateTimeInterface { return $this->from; }
    public function to(): \DateTimeInterface   { return $this->to; }
    public function contains(\DateTimeInterface $date): bool { return $date >= $this->from && $date <= $this->to; }
    public function daysSpan(): int { return (int) $this->from->diff($this->to)->days; }
}
