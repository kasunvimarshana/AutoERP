<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleOwnershipType: string
{
    case Owned = 'owned';
    case CustomerOwned = 'customer_owned';
    case Leased = 'leased';
    case Rented = 'rented';
    case CompanyOwned = 'company_owned';
    case ThirdParty = 'third_party';
}
