<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCustodyItemType: string
{
    case Condition = 'condition';
    case Damage = 'damage';
    case Accessory = 'accessory';
    case Document = 'document';
    case Key = 'key';
    case Fuel = 'fuel';
    case Other = 'other';
}
