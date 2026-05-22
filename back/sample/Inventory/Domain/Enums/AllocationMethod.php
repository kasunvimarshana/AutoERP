<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum AllocationMethod: string
{
    case FIFO = 'FIFO';
    case LIFO = 'LIFO';
    case FEFO = 'FEFO';
    case BATCH = 'BATCH';
    case LOT = 'LOT';
    case SERIAL = 'SERIAL';
    case LOCATION_PRIORITY = 'LOCATION_PRIORITY';
    case RESERVATION = 'RESERVATION';
    case RULE_BASED = 'RULE_BASED';

    public static function fromNullable(?string $value, self $fallback = self::FIFO): self
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return self::tryFrom(strtoupper($value)) ?? $fallback;
    }
}
