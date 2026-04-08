<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class CustomerType extends ValueObject
{
    public const INDIVIDUAL = 'individual';
    public const BUSINESS   = 'business';

    private const VALID = [self::INDIVIDUAL, self::BUSINESS];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid customer type '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public static function individual(): self
    {
        return new self(self::INDIVIDUAL);
    }

    public static function business(): self
    {
        return new self(self::BUSINESS);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isIndividual(): bool
    {
        return $this->value === self::INDIVIDUAL;
    }

    public function isBusiness(): bool
    {
        return $this->value === self::BUSINESS;
    }

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public static function fromArray(array $data): static
    {
        return new static($data['value']);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /** @return string[] */
    public static function values(): array
    {
        return self::VALID;
    }
}
