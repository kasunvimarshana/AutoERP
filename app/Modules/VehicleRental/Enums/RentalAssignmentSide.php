<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAssignmentSide: string
{
    case OwnerSupply = 'owner_supply';
    case CustomerUse = 'customer_use';
}
