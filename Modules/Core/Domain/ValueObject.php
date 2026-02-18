<?php

declare(strict_types=1);

namespace Modules\Core\Domain;

/**
 * Base Value Object
 *
 * Value objects are immutable and defined by their attributes
 * They have no identity
 */
abstract class ValueObject
{
    /**
     * Check if two value objects are equal
     */
    abstract public function equals(ValueObject $other): bool;

    /**
     * Get value object as array
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * String representation
     */
    abstract public function __toString(): string;
}
