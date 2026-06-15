<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleOwnerType: string
{
    case Company = 'company';
    case Customer = 'customer';
    case Supplier = 'supplier';
    case ThirdParty = 'third_party';
}
