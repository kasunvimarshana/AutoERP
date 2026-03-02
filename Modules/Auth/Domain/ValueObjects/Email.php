<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

final class Email
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalised = strtolower(trim($value));

        if (! filter_var($normalised, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: \"{$value}\".");
        }

        $this->value = $normalised;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
