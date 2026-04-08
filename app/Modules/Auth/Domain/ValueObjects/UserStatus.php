<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\ValueObjects;

use Modules\Core\Domain\ValueObjects\ValueObject;

final class UserStatus extends ValueObject
{
    public const ACTIVE    = 'active';
    public const INACTIVE  = 'inactive';
    public const SUSPENDED = 'suspended';
    public const PENDING   = 'pending';

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, [self::ACTIVE, self::INACTIVE, self::SUSPENDED, self::PENDING], true)) {
            throw new \InvalidArgumentException("Invalid user status: {$value}");
        }

        $this->value = $value;
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function inactive(): self
    {
        return new self(self::INACTIVE);
    }

    public static function suspended(): self
    {
        return new self(self::SUSPENDED);
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->value === self::SUSPENDED;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
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

    /**
     * All valid status values.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return [self::ACTIVE, self::INACTIVE, self::SUSPENDED, self::PENDING];
    }
}
