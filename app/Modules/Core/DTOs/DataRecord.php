<?php

declare(strict_types=1);

namespace Modules\Core\DTOs;

use InvalidArgumentException;
use OutOfBoundsException;

final readonly class DataRecord
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(public array $values)
    {
        foreach (array_keys($this->values) as $key) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('DataRecord keys must be strings.');
            }
        }
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->values[$key] : $default;
    }

    public function require(string $key): mixed
    {
        if (! array_key_exists($key, $this->values)) {
            throw new OutOfBoundsException(sprintf('Required field "%s" is missing from DataRecord.', $key));
        }

        return $this->values[$key];
    }

    public function id(string $key = 'id'): int|string
    {
        $value = $this->require($key);
        if (! is_int($value) && ! is_string($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be int|string.', $key));
        }

        if (is_string($value) && trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Field "%s" cannot be empty.', $key));
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
