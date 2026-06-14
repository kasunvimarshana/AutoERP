<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAgreementVehicleLinkStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
}
