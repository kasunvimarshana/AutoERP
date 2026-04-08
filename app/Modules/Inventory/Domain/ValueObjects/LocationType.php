<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

class LocationType
{
    public const BIN        = 'bin';
    public const RACK       = 'rack';
    public const SHELF      = 'shelf';
    public const FLOOR      = 'floor';
    public const RECEIVING  = 'receiving';
    public const SHIPPING   = 'shipping';
    public const STAGING    = 'staging';
    public const QUARANTINE = 'quarantine';

    public const VALID_TYPES = [
        self::BIN,
        self::RACK,
        self::SHELF,
        self::FLOOR,
        self::RECEIVING,
        self::SHIPPING,
        self::STAGING,
        self::QUARANTINE,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (! in_array($value, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException("Invalid location type: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isBin(): bool
    {
        return $this->value === self::BIN;
    }

    public function isRack(): bool
    {
        return $this->value === self::RACK;
    }

    public function isShelf(): bool
    {
        return $this->value === self::SHELF;
    }

    public function isFloor(): bool
    {
        return $this->value === self::FLOOR;
    }

    public function isReceiving(): bool
    {
        return $this->value === self::RECEIVING;
    }

    public function isShipping(): bool
    {
        return $this->value === self::SHIPPING;
    }

    public function isStaging(): bool
    {
        return $this->value === self::STAGING;
    }

    public function isQuarantine(): bool
    {
        return $this->value === self::QUARANTINE;
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
        return self::VALID_TYPES;
    }
}
