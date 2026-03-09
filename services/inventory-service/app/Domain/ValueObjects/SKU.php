<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class SKU
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));
        $this->validate($normalized);
        $this->value = $normalized;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function validate(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException('SKU cannot be empty.');
        }

        if (strlen($value) > 100) {
            throw new InvalidArgumentException('SKU cannot exceed 100 characters.');
        }

        if (!preg_match('/^[A-Z0-9\-_\.]+$/', $value)) {
            throw new InvalidArgumentException(
                "Invalid SKU format: '{$value}'. Only uppercase letters, digits, hyphens, underscores, and dots are allowed."
            );
        }
    }
}
