<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Enums;

enum BillingCycleMode: string
{
    case WithoutRunningChart = 'without_running_chart';
    case WithRunningChart = 'with_running_chart';
}
