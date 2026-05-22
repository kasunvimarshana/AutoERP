<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum ValuationMethod: string
{
    case FIFO = 'FIFO';
    case LIFO = 'LIFO';
    case WEIGHTED_AVERAGE = 'WEIGHTED_AVERAGE';
    case MOVING_AVERAGE = 'MOVING_AVERAGE';
    case STANDARD_COST = 'STANDARD_COST';
    case SPECIFIC_IDENTIFICATION = 'SPECIFIC_IDENTIFICATION';
    case REPLACEMENT_COST = 'REPLACEMENT_COST';

    public static function fromNullable(?string $value, self $fallback = self::FIFO): self
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return self::tryFrom(strtoupper($value)) ?? $fallback;
    }
}
