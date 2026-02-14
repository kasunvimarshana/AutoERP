<?php

namespace App\DTOs;

use JsonSerializable;

/**
 * Class BaseDTO
 * 
 * Base Data Transfer Object providing common DTO patterns
 * 
 * @package App\DTOs
 */
abstract class BaseDTO implements JsonSerializable
{
    /**
     * Create DTO from array
     *
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(...$data);
    }

    /**
     * Convert DTO to array
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [];
        
        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof self) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = array_map(function ($item) {
                    return $item instanceof self ? $item->toArray() : $item;
                }, $value);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * JSON serialize
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
