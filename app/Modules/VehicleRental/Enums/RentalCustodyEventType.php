<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCustodyEventType: string
{
    case Handover = 'handover';
    case Return = 'return';
    case ReplacementOut = 'replacement_out';
    case ReplacementIn = 'replacement_in';
}
