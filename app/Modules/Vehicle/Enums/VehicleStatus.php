<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case UnderService = 'under_service';
    case Rented = 'rented';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Blocked = 'blocked';
    case Scrapped = 'scrapped';
}
