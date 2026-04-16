<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

class Money extends ValueObject
{
    private float $amount;

    private string $currency;

    public function __construct(float $amount, string $currency = 'USD')
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException("Amount cannot be negative: {$amount}");
        }
        $this->amount = $amount;
        $this->currency = strtoupper($currency);
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
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Currency mismatch');
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amount * $factor, $this->currency);
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['amount'], $data['currency']);
    }
}
