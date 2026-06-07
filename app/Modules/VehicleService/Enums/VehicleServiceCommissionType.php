<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceCommissionType: string
{
    case None = 'none';
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
