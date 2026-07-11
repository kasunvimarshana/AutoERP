<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalDepositLinkStatus: string
{
    case Active = 'active';
    case Reversed = 'reversed';
}
