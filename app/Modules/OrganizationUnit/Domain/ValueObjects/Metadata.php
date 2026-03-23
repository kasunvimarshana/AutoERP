<?php

namespace Modules\OrganizationUnit\Domain\ValueObjects;

use Modules\Core\Domain\ValueObjects\ValueObject;

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
