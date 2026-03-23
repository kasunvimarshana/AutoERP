<?php

namespace Modules\OrganizationUnit\Domain\ValueObjects;

use Modules\Core\Domain\ValueObjects\ValueObject;

class Name extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if (empty($value)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value']);
    }
}
