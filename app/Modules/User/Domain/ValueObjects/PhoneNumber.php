<?php

namespace Modules\User\Domain\ValueObjects;

use Modules\Core\Domain\ValueObjects\ValueObject;

class PhoneNumber extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (!preg_match('/^[\+]?[0-9\s\-\(\)]{5,20}$/', $value)) {
            throw new \InvalidArgumentException("Invalid phone number: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string { return $this->value; }
    public function toArray(): array { return ['value' => $this->value]; }
    public static function fromArray(array $data): static { return new static($data['value']); }
}
