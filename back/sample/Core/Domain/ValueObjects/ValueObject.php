<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

abstract class ValueObject
{
    /**
     * Compare two value objects for equality.
     */
    public function equals(ValueObject $other): bool
    {
        return get_class($this) === get_class($other) && $this->toArray() === $other->toArray();
    }

    /**
     * Convert the value object to an array.
     */
    abstract public function toArray(): array;

    /**
     * Create a value object from an array.
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Magic method for debugging.
     */
    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
