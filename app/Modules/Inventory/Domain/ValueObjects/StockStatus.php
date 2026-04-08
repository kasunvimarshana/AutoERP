<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

class StockStatus
{
    public const AVAILABLE  = 'available';
    public const RESERVED   = 'reserved';
    public const ACTIVE     = 'active';
    public const QUARANTINE = 'quarantine';
    public const EXPIRED    = 'expired';
    public const DEPLETED   = 'depleted';
    public const RECALLED   = 'recalled';

    public const VALID_STATUSES = [
        self::AVAILABLE,
        self::RESERVED,
        self::ACTIVE,
        self::QUARANTINE,
        self::EXPIRED,
        self::DEPLETED,
        self::RECALLED,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid stock status: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isAvailable(): bool
    {
        return $this->value === self::AVAILABLE;
    }

    public function isReserved(): bool
    {
        return $this->value === self::RESERVED;
    }

    public function isQuarantine(): bool
    {
        return $this->value === self::QUARANTINE;
    }

    public function isExpired(): bool
    {
        return $this->value === self::EXPIRED;
    }

    public function isDepleted(): bool
    {
        return $this->value === self::DEPLETED;
    }

    public function isRecalled(): bool
    {
        return $this->value === self::RECALLED;
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
