<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalChargeStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
}
