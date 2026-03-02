<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

final class TenantId
{
    public function __construct(
        private readonly int $value
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException('TenantId must be a positive integer.');
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function equals(TenantId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
