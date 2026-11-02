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

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Converted => 'Converted',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }
}
