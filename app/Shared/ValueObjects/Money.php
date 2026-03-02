<?php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    private const SCALE = 4;

    public function __construct(
        public readonly string $amount,
        public readonly string $currency,
    ) {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException("Invalid monetary amount: {$amount}");
        }
        if (strlen(trim($currency)) !== 3) {
            throw new InvalidArgumentException("Currency must be an ISO 4217 code: {$currency}");
        }
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(bcadd($this->amount, $other->amount, self::SCALE), $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(bcsub($this->amount, $other->amount, self::SCALE), $this->currency);
    }

    public function multiply(string $factor): self
    {
        return new self(bcmul($this->amount, $factor, self::SCALE), $this->currency);
    }

    public function toDisplay(): string
    {
        return number_format((float) $this->amount, 2, '.', ',');
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'display' => $this->toDisplay(),
        ];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }
}
