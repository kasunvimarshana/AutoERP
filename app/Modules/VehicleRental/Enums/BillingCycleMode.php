<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum BillingCycleMode: string
{
    case WithoutRunningChart = 'without_running_chart';
    case WithRunningChart = 'with_running_chart';
}
