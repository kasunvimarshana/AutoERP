<?php

declare(strict_types=1);

namespace Modules\Product\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class SKU
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = strtoupper(trim($value));

        if ($trimmed === '') {
            throw new InvalidArgumentException('SKU cannot be empty.');
        }

        if (strlen($trimmed) > 100) {
            throw new InvalidArgumentException('SKU must not exceed 100 characters.');
        }

        if (! preg_match('/^[A-Z0-9\-_\.]+$/', $trimmed)) {
            throw new InvalidArgumentException(
                "SKU must contain only uppercase letters, digits, hyphens, underscores, or dots: {$trimmed}"
            );
        }

        $this->value = $trimmed;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
