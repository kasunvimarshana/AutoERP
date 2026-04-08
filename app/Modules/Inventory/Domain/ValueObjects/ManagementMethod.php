<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

class ManagementMethod
{
    public const PERPETUAL = 'perpetual';
    public const PERIODIC  = 'periodic';

    public const VALID_METHODS = [
        self::PERPETUAL,
        self::PERIODIC,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_METHODS, true)) {
            throw new InvalidArgumentException("Invalid management method: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPerpetual(): bool
    {
        return $this->value === self::PERPETUAL;
    }

    public function isPeriodic(): bool
    {
        return $this->value === self::PERIODIC;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function assertValid(string $value): void
    {
        if (! in_array($value, self::VALID_METHODS, true)) {
            throw new InvalidArgumentException("Invalid management method: {$value}");
        }
    }

    public static function values(): array
    {
        return self::VALID_METHODS;
    }
}
