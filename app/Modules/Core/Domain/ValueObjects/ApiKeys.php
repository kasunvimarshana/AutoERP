<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

class ApiKeys extends ValueObject
{
    private array $keys;

    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    public function get(string $service): ?string
    {
        return $this->keys[$service] ?? null;
    }

    public function toArray(): array
    {
        return $this->keys;
    }

    public static function fromArray(array $data): static
    {
        return new static($data);
    }
}
