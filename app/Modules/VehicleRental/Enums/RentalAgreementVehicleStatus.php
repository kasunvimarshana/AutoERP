<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAgreementVehicleStatus: string
{
    case Allocated = 'allocated';
    case Active = 'active';
    case Replaced = 'replaced';
    case Returned = 'returned';
}
