<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

class Sku extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if (empty($value)) {
            throw new \InvalidArgumentException('SKU cannot be empty');
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
