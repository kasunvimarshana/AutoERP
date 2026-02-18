<?php

declare(strict_types=1);

namespace Modules\POS\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case SEATED = 'seated';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
