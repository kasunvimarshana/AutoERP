<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Base value object.
 *
 * Value objects are immutable and equality is determined by value, not identity.
 * All module value objects must extend this class.
 */
abstract class ValueObject
{
    /**
     * Determine whether two value objects are equal.
     */
    public function equals(self $other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        return $this->toArray() === $other->toArray();
    }

    /**
     * Return the scalar representation of the value object.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * Assert a condition; throw InvalidArgumentException if it fails.
     */
    protected function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new InvalidArgumentException($message);
        }
    }
}
