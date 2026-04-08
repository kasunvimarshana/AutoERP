<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Core\Domain\ValueObjects\ValueObject;

final class CustomerStatus extends ValueObject
{
    public const ACTIVE   = 'active';
    public const INACTIVE = 'inactive';
    public const BLOCKED  = 'blocked';

    private const VALID = [self::ACTIVE, self::INACTIVE, self::BLOCKED];

    public function __construct(public readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new InvalidArgumentException(
                "Invalid customer status '{$value}'. Must be one of: " . implode(', ', self::VALID)
            );
        }
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function inactive(): self
    {
        return new self(self::INACTIVE);
    }

    public static function blocked(): self
    {
        return new self(self::BLOCKED);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isBlocked(): bool
    {
        return $this->value === self::BLOCKED;
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
