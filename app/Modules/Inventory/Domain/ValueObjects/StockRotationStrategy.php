<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

class StockRotationStrategy
{
    /** First In, First Out */
    public const FIFO = 'fifo';

    /** First Expired, First Out */
    public const FEFO = 'fefo';

    /** Last In, First Out */
    public const LIFO = 'lifo';

    /** First Manufactured, First Out */
    public const FMFO = 'fmfo';

    public const VALID_STRATEGIES = [
        self::FIFO,
        self::FEFO,
        self::LIFO,
        self::FMFO,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_STRATEGIES, true)) {
            throw new InvalidArgumentException("Invalid stock rotation strategy: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isFifo(): bool
    {
        return $this->value === self::FIFO;
    }

    public function isFefo(): bool
    {
        return $this->value === self::FEFO;
    }

    public function isLifo(): bool
    {
        return $this->value === self::LIFO;
    }

    public function isFmfo(): bool
    {
        return $this->value === self::FMFO;
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
        if (! in_array($value, self::VALID_STRATEGIES, true)) {
            throw new InvalidArgumentException("Invalid stock rotation strategy: {$value}");
        }
    }

    public static function values(): array
    {
        return self::VALID_STRATEGIES;
    }
}
