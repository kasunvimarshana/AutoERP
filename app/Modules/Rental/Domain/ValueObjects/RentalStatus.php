<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\ValueObjects;

enum RentalStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Active    = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $new): bool
    {
        return match ($this) {
            self::Pending   => in_array($new, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($new, [self::Active, self::Cancelled], true),
            self::Active    => in_array($new, [self::Completed, self::Cancelled], true),
            self::Completed,
            self::Cancelled => false,
        };
    }
}
