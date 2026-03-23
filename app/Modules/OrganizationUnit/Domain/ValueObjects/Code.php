<?php

namespace Modules\OrganizationUnit\Domain\ValueObjects;

use Modules\Core\Domain\ValueObjects\ValueObject;

class Code extends ValueObject
{
    private ?string $value;

    public function __construct(?string $value)
    {
        $this->value = $value !== null ? trim($value) : null;
    }

    public function value(): ?string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value'] ?? null);
    }
}
