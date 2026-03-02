<?php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use InvalidArgumentException;

final readonly class TenantId
{
    public function __construct(public readonly int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException("TenantId must be a positive integer, got: {$value}");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
