<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceLifecycleDimension: string
{
    case Operational = 'operational';
    case Billing = 'billing';
    case Payment = 'payment';
}
