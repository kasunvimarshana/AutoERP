<?php

namespace App\Core\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Base Data Transfer Object
 *
 * Provides type-safe data transfer between layers.
 * Ensures data integrity and validation.
 */
abstract class BaseDTO implements Arrayable, JsonSerializable
{
    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): static
    {
        return new static(...$data);
    }

    /**
     * Create DTO from request.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public static function fromRequest($request): static
    {
        return static::fromArray($request->validated());
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $data = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $data[$name] = $this->{$name};
        }

        return $data;
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Validate the DTO data.
     */
    abstract public function validate(): bool;
}
