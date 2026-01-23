<?php

declare(strict_types=1);

namespace App\Core\Enums;

/**
 * Cache Duration Enum
 *
 * Defines standard cache durations in seconds
 */
enum CacheDuration: int
{
    case ONE_MINUTE = 60;
    case FIVE_MINUTES = 300;
    case FIFTEEN_MINUTES = 900;
    case THIRTY_MINUTES = 1800;
    case ONE_HOUR = 3600;
    case SIX_HOURS = 21600;
    case TWELVE_HOURS = 43200;
    case ONE_DAY = 86400;
    case ONE_WEEK = 604800;
    case ONE_MONTH = 2592000;

    /**
     * Get duration label
     */
    public function label(): string
    {
        return match ($this) {
            self::ONE_MINUTE => '1 Minute',
            self::FIVE_MINUTES => '5 Minutes',
            self::FIFTEEN_MINUTES => '15 Minutes',
            self::THIRTY_MINUTES => '30 Minutes',
            self::ONE_HOUR => '1 Hour',
            self::SIX_HOURS => '6 Hours',
            self::TWELVE_HOURS => '12 Hours',
            self::ONE_DAY => '1 Day',
            self::ONE_WEEK => '1 Week',
            self::ONE_MONTH => '1 Month',
        };
    }

    /**
     * Get duration in minutes
     */
    public function toMinutes(): int
    {
        return (int) ($this->value / 60);
    }
}
