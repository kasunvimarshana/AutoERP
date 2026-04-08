<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

class ValuationMethod
{
    public const FIFO                    = 'fifo';
    public const LIFO                    = 'lifo';
    public const AVCO                    = 'avco';
    public const STANDARD_COST           = 'standard_cost';
    public const SPECIFIC_IDENTIFICATION = 'specific_identification';

    public const VALID_METHODS = [
        self::FIFO,
        self::LIFO,
        self::AVCO,
        self::STANDARD_COST,
        self::SPECIFIC_IDENTIFICATION,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_METHODS, true)) {
            throw new InvalidArgumentException("Invalid valuation method: {$value}");
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

    public function isLifo(): bool
    {
        return $this->value === self::LIFO;
    }

    public function isAvco(): bool
    {
        return $this->value === self::AVCO;
    }

    public function isStandardCost(): bool
    {
        return $this->value === self::STANDARD_COST;
    }

    public function isSpecificIdentification(): bool
    {
        return $this->value === self::SPECIFIC_IDENTIFICATION;
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
            throw new InvalidArgumentException("Invalid valuation method: {$value}");
        }
    }

    public static function values(): array
    {
        return self::VALID_METHODS;
    }
}
