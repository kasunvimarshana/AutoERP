<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\ValueObjects;

final class WarehouseType
{
    public const STANDARD = 'standard';
    public const VIRTUAL  = 'virtual';
    public const TRANSIT  = 'transit';
    public const EXTERNAL = 'external';

    public const ALL = [self::STANDARD, self::VIRTUAL, self::TRANSIT, self::EXTERNAL];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new \InvalidArgumentException("Invalid warehouse type: {$value}");
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

    public function __toString(): string
    {
        return $this->value;
    }
}
