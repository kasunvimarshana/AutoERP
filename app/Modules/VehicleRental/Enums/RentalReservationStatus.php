<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalReservationStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Converted = 'converted';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
