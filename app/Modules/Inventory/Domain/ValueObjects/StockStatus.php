<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

final class StockStatus
{
    public const AVAILABLE  = 'available';
    public const RESERVED   = 'reserved';
    public const IN_TRANSIT = 'in_transit';
    public const QUARANTINE = 'quarantine';
    public const SCRAPPED   = 'scrapped';

    public const ALL = [
        self::AVAILABLE,
        self::RESERVED,
        self::IN_TRANSIT,
        self::QUARANTINE,
        self::SCRAPPED,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::ALL, true)) {
            throw new \InvalidArgumentException("Invalid stock status: {$value}");
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isAvailable(): bool
    {
        return $this->value === self::AVAILABLE;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
