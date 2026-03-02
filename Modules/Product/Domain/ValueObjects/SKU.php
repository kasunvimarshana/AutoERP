<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

final class SKU
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = strtoupper(trim($value));

        if (empty($trimmed)) {
            throw new \InvalidArgumentException('SKU cannot be empty.');
        }

        if (! preg_match('/^[A-Z0-9][A-Z0-9\-]{0,49}$/', $trimmed)) {
            throw new \InvalidArgumentException(
                "Invalid SKU format \"{$value}\". Must be alphanumeric with optional dashes, up to 50 characters."
            );
        }

        $this->value = $trimmed;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(SKU $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
