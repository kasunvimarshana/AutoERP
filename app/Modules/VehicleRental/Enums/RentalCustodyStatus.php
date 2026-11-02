<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCustodyStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Reversed = 'reversed';
}
