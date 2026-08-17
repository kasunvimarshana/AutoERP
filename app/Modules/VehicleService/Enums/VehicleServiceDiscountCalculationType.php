<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceDiscountCalculationType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
