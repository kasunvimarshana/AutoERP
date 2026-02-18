<?php

declare(strict_types=1);

namespace Modules\Core\Domain;

/**
 * Base Entity Class
 *
 * All domain entities should extend this class
 * Entities have identity and lifecycle
 */
abstract class Entity
{
    /**
     * The entity's unique identifier
     */
    protected string|int|null $id = null;

    /**
     * Get the entity's identifier
     */
    public function getId(): string|int|null
    {
        return $this->id;
    }

    /**
     * Check if entity has an ID
     */
    public function hasId(): bool
    {
        return $this->id !== null;
    }

    /**
     * Check if two entities are the same
     */
    public function equals(Entity $other): bool
    {
        if (! ($other instanceof static)) {
            return false;
        }

        if ($this->id === null || $other->id === null) {
            return false;
        }

        return $this->id === $other->id;
    }

    /**
     * Get entity as array
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;
}
