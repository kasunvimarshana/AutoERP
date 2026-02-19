<?php

declare(strict_types=1);

namespace App\Core\DTOs;

use JsonSerializable;

/**
 * Base Data Transfer Object
 *
 * Abstract base class for all DTOs in the application
 * Provides immutable data structures for transferring data between layers
 */
abstract class BaseDTO implements JsonSerializable
{
    /**
     * Convert DTO to array
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * Create DTO from array
     *
     * @param  array<string, mixed>  $data
     */
    abstract public static function fromArray(array $data): static;

    /**
     * JSON serialize the DTO
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert DTO to JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
