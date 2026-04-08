<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

final class MovementType
{
    public const RECEIPT    = 'receipt';
    public const ISSUE      = 'issue';
    public const TRANSFER   = 'transfer';
    public const ADJUSTMENT = 'adjustment';
    public const RETURN_IN  = 'return_in';
    public const RETURN_OUT = 'return_out';
    public const SCRAP      = 'scrap';

    public const ALL = [
        self::RECEIPT,
        self::ISSUE,
        self::TRANSFER,
        self::ADJUSTMENT,
        self::RETURN_IN,
        self::RETURN_OUT,
        self::SCRAP,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new \InvalidArgumentException("Invalid movement type: {$value}");
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isOutbound(): bool
    {
        return in_array($this->value, [self::ISSUE, self::RETURN_OUT, self::SCRAP, self::TRANSFER], true);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
