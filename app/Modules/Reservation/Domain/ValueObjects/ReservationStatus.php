<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\ValueObjects;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Fulfilled = 'fulfilled';
}
