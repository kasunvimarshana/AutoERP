<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

class SerialStatus
{
    public const AVAILABLE  = 'available';
    public const RESERVED   = 'reserved';
    public const SOLD       = 'sold';
    public const RETURNED   = 'returned';
    public const DAMAGED    = 'damaged';
    public const SCRAPPED   = 'scrapped';
    public const IN_TRANSIT = 'in_transit';

    public const VALID_STATUSES = [
        self::AVAILABLE,
        self::RESERVED,
        self::SOLD,
        self::RETURNED,
        self::DAMAGED,
        self::SCRAPPED,
        self::IN_TRANSIT,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid serial status: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isAvailable(): bool
    {
        return $this->value === self::AVAILABLE;
    }

    public function isReserved(): bool
    {
        return $this->value === self::RESERVED;
    }

    public function isSold(): bool
    {
        return $this->value === self::SOLD;
    }

    public function isReturned(): bool
    {
        return $this->value === self::RETURNED;
    }

    public function isDamaged(): bool
    {
        return $this->value === self::DAMAGED;
    }

    public function isScrapped(): bool
    {
        return $this->value === self::SCRAPPED;
    }

    public function isInTransit(): bool
    {
        return $this->value === self::IN_TRANSIT;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function values(): array
    {
        return self::VALID_STATUSES;
    }
}
