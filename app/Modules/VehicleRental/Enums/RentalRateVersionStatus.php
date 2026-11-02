<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalRateVersionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Superseded = 'superseded';
    case Cancelled = 'cancelled';
}
