<?php

namespace App\Domain\Order\ValueObjects;

use App\Domain\Shared\ValueObjects\AbstractValueObject;
use App\Domain\Shared\ValueObjects\Uuid;

/**
 * OrderId wraps a UUID and provides a domain-specific type
 * so Order IDs cannot be accidentally swapped with Customer IDs, etc.
 */
final class OrderId extends AbstractValueObject
{
    private function __construct(private readonly Uuid $uuid)
    {
    }

    public static function generate(): self
    {
        return new self(Uuid::generate());
    }

    public static function from(string $value): self
    {
        return new self(Uuid::from($value));
    }

    public function value(): string
    {
        return $this->uuid->value();
    }

    public function equals(AbstractValueObject $other): bool
    {
        return $other instanceof self && $this->uuid->equals($other->uuid);
    }
}
