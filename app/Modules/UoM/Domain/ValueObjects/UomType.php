<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\ValueObjects;

use InvalidArgumentException;

class UomType
{
    public const BASE = 'base';

    public const PURCHASE = 'purchase';

    public const SALES = 'sales';

    public const INVENTORY = 'inventory';

    public const VALID_TYPES = [self::BASE, self::PURCHASE, self::SALES, self::INVENTORY];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid UoM type "%s". Valid types are: %s.', $value, implode(', ', self::VALID_TYPES))
            );
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isBase(): bool
    {
        return $this->value === self::BASE;
    }

    public function isPurchase(): bool
    {
        return $this->value === self::PURCHASE;
    }

    public function isSales(): bool
    {
        return $this->value === self::SALES;
    }

    public function isInventory(): bool
    {
        return $this->value === self::INVENTORY;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
