<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalPartyType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Owner = 'owner';
}
