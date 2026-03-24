<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

class Metadata extends ValueObject
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public static function fromArray(array $data): static
    {
        return new static($data);
    }
}
