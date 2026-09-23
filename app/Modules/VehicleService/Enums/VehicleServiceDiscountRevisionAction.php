<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceDiscountRevisionAction: string
{
    case Set = 'set';
    case Removed = 'removed';
}
