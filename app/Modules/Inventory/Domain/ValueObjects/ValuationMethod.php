<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

final class ValuationMethod
{
    public const FIFO     = 'fifo';
    public const LIFO     = 'lifo';
    public const FEFO     = 'fefo';
    public const AVERAGE  = 'average';
    public const STANDARD = 'standard';

    public const ALL = [
        self::FIFO,
        self::LIFO,
        self::FEFO,
        self::AVERAGE,
        self::STANDARD,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new \InvalidArgumentException("Invalid valuation method: {$value}");
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
