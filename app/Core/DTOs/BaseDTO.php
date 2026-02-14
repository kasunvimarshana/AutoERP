<?php

namespace App\Core\DTOs;

use Illuminate\Support\Arr;

abstract class BaseDTO
{
    public function __construct(array $data = [])
    {
        $this->map($data);
    }

    protected function map(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function toArray(): array
    {
        $array = [];
        foreach (get_object_vars($this) as $key => $value) {
            $array[$key] = $value;
        }
        return $array;
    }

    public static function fromArray(array $data): static
    {
        return new static($data);
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
