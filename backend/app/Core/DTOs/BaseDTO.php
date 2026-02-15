<?php

declare(strict_types=1);

namespace App\Core\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Base Data Transfer Object
 * 
 * Provides a foundation for all DTOs with type safety and validation
 */
abstract class BaseDTO implements Arrayable, JsonSerializable
{
    /**
     * Convert the DTO to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Convert the DTO to JSON
     *
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Create DTO from array data
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(...$data);
    }

    /**
     * Validate DTO data
     * 
     * @throws \InvalidArgumentException
     */
    abstract public function validate(): void;
}
