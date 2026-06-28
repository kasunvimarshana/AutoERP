<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleOwnerType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Company = 'company';
}
