<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\ValueObjects;
final class Money {
    private const SCALE = 4;
    private function __construct(
        private readonly string $amount,
        private readonly string $currency,
    ) {}
    public static function of(string $amount, string $currency): self {
        if (!is_numeric($amount)) throw new \InvalidArgumentException("Invalid amount: \"{$amount}\".");
        if (strlen($currency) !== 3) throw new \InvalidArgumentException('Currency code must be 3 characters.');
        return new self(bcadd($amount, '0', self::SCALE), strtoupper($currency));
    }
    public static function zero(string $currency): self { return self::of('0', $currency); }
    public function getAmount(): string { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    private function assertSameCurrency(self $other): void {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}.");
        }
    }
    public function add(self $other): self {
        $this->assertSameCurrency($other);
        return new self(bcadd($this->amount, $other->amount, self::SCALE), $this->currency);
    }
    public function subtract(self $other): self {
        $this->assertSameCurrency($other);
        return new self(bcsub($this->amount, $other->amount, self::SCALE), $this->currency);
    }
    public function multiply(string $factor): self {
        return new self(bcmul($this->amount, $factor, self::SCALE), $this->currency);
    }
    public function divide(string $divisor): self {
        if (bccomp($divisor, '0', self::SCALE) === 0) throw new \DivisionByZeroError('Cannot divide by zero.');
        return new self(bcdiv($this->amount, $divisor, self::SCALE), $this->currency);
    }
    public function isZero(): bool { return bccomp($this->amount, '0', self::SCALE) === 0; }
    public function isPositive(): bool { return bccomp($this->amount, '0', self::SCALE) > 0; }
    public function isNegative(): bool { return bccomp($this->amount, '0', self::SCALE) < 0; }
    public function equals(self $other): bool {
        $this->assertSameCurrency($other);
        return bccomp($this->amount, $other->amount, self::SCALE) === 0;
    }
    public function __toString(): string { return "{$this->currency} {$this->amount}"; }
}
