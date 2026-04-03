<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

class AllocationAlgorithm
{
    /** First Expired, First Out — prioritises nearest expiry */
    public const FEFO = 'fefo';

    /** First In, First Out — prioritises oldest receipt */
    public const FIFO = 'fifo';

    /** Last In, First Out — prioritises newest receipt */
    public const LIFO = 'lifo';

    /** Zone-based — allocates from nearest or most efficient warehouse zone */
    public const ZONE_BASED = 'zone_based';

    /** Demand-based — allocates to fulfil highest-priority or time-sensitive demand first */
    public const DEMAND_BASED = 'demand_based';

    public const VALID_ALGORITHMS = [
        self::FEFO,
        self::FIFO,
        self::LIFO,
        self::ZONE_BASED,
        self::DEMAND_BASED,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_ALGORITHMS, true)) {
            throw new InvalidArgumentException("Invalid allocation algorithm: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isFefo(): bool
    {
        return $this->value === self::FEFO;
    }

    public function isFifo(): bool
    {
        return $this->value === self::FIFO;
    }

    public function isLifo(): bool
    {
        return $this->value === self::LIFO;
    }

    public function isZoneBased(): bool
    {
        return $this->value === self::ZONE_BASED;
    }

    public function isDemandBased(): bool
    {
        return $this->value === self::DEMAND_BASED;
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
        if (! in_array($value, self::VALID_ALGORITHMS, true)) {
            throw new InvalidArgumentException("Invalid allocation algorithm: {$value}");
        }
    }

    public static function values(): array
    {
        return self::VALID_ALGORITHMS;
    }
}
