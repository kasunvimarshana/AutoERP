<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Email Value Object
 * 
 * Immutable representation of an email address
 */
final class Email implements JsonSerializable, Stringable
{
    private function __construct(private readonly string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address: {$value}");
        }
    }

    public static function fromString(string $email): self
    {
        return new self(strtolower(trim($email)));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        return explode('@', $this->value)[1];
    }

    public function getLocalPart(): string
    {
        return explode('@', $this->value)[0];
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
