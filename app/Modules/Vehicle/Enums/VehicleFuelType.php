<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleFuelType: string
{
    case Petrol = 'petrol';
    case Diesel = 'diesel';
    case Hybrid = 'hybrid';
    case Electric = 'electric';
    case LPG = 'lpg';
    case CNG = 'cng';
    case Other = 'other';
}
