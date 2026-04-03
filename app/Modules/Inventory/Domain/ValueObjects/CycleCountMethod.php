<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

class CycleCountMethod
{
    /** ABC analysis — high-value items counted most frequently */
    public const ABC = 'abc';

    /** Frequency-based — items counted based on a fixed time interval */
    public const FREQUENCY = 'frequency';

    /** Random sampling — items selected at random for spot counts */
    public const RANDOM = 'random';

    /** Periodic full count — complete physical inventory at set intervals */
    public const PERIODIC = 'periodic';

    public const VALID_METHODS = [
        self::ABC,
        self::FREQUENCY,
        self::RANDOM,
        self::PERIODIC,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_METHODS, true)) {
            throw new InvalidArgumentException("Invalid cycle count method: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isAbc(): bool
    {
        return $this->value === self::ABC;
    }

    public function isFrequency(): bool
    {
        return $this->value === self::FREQUENCY;
    }

    public function isRandom(): bool
    {
        return $this->value === self::RANDOM;
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
            throw new InvalidArgumentException("Invalid cycle count method: {$value}");
        }
    }

    public static function values(): array
    {
        return self::VALID_METHODS;
    }
}
