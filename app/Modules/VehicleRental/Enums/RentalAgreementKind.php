<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAgreementKind: string
{
    case CustomerRental = 'customer_rental';
    case OwnerSupply = 'owner_supply';
}
