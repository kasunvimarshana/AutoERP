<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalVehicleSourceType: string
{
    case CompanyOwned = 'company_owned';
    case OwnerSupplied = 'owner_supplied';
    case Financed = 'financed';
}
