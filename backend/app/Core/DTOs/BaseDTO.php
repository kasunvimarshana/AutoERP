<?php

namespace App\Core\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * BaseDTO
 * 
 * Base Data Transfer Object class
 * DTOs are used to transfer data between layers (Controller → Service → Repository)
 * enforcing type safety and immutability
 */
abstract class BaseDTO implements Arrayable, JsonSerializable
{
    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): static
    {
        return new static(...$data);
    }

    /**
     * Create DTO from request
     */
    public static function fromRequest($request): static
    {
        return static::fromArray($request->validated());
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        
        $array = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $this->$name;
            
            // Convert nested DTOs to arrays
            if ($value instanceof Arrayable) {
                $array[$name] = $value->toArray();
            } elseif (is_array($value)) {
                $array[$name] = array_map(function ($item) {
                    return $item instanceof Arrayable ? $item->toArray() : $item;
                }, $value);
            } else {
                $array[$name] = $value;
            }
        }
        
        return $array;
    }

    /**
     * Convert DTO to JSON serializable format
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Filter null values from array
     */
    protected static function filterNulls(array $data): array
    {
        return array_filter($data, fn($value) => $value !== null);
    }
}
