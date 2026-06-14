<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAgreementStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Active = 'active';
    case Returned = 'returned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
