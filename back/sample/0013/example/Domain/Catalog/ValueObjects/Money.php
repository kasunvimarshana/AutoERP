<?php

declare(strict_types=1);

namespace App\Domain\Catalog\ValueObjects;

final class Money
{
    private function __construct(
        private readonly int    $amount,
        private readonly string $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative.');
        }
    }

    public static function of(int $amountInMinorUnits, string $currency): self
    {
        return new self($amountInMinorUnits, strtoupper($currency));
    }

    public static function fromDecimal(string $decimal, string $currency): self
    {
        if (! is_numeric($decimal)) {
            throw new \InvalidArgumentException("Non-numeric decimal: [{$decimal}].");
        }

        return new self((int) round((float) $decimal * 100), strtoupper($currency));
    }

    public function amount(): int    { return $this->amount; }
    public function currency(): string { return $this->currency; }
    public function toDecimal(): float { return $this->amount / 100; }
    public function isZero(): bool   { return $this->amount === 0; }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function formatted(): string
    {
        return sprintf('%s %.2f', $this->currency, $this->toDecimal());
    }

    public function toArray(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency];
    }

    public function __toString(): string { return $this->formatted(); }
}
