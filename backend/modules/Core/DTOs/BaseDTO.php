<?php

namespace Modules\Core\DTOs;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

abstract class BaseDTO
{
    public function __construct(array $data = [])
    {
        $this->fill($data);
    }

    protected function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function toArray(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $data = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $this->{$name};

            if ($value instanceof self) {
                $data[$name] = $value->toArray();
            } elseif ($value instanceof Collection) {
                $data[$name] = $value->map(function ($item) {
                    return $item instanceof self ? $item->toArray() : $item;
                })->toArray();
            } elseif (is_array($value)) {
                $data[$name] = array_map(function ($item) {
                    return $item instanceof self ? $item->toArray() : $item;
                }, $value);
            } else {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public static function fromArray(array $data): static
    {
        return new static($data);
    }

    public static function collection(array $items): Collection
    {
        return collect($items)->map(fn ($item) => static::fromArray($item));
    }

    public function only(array $keys): array
    {
        return Arr::only($this->toArray(), $keys);
    }

    public function except(array $keys): array
    {
        return Arr::except($this->toArray(), $keys);
    }
}
