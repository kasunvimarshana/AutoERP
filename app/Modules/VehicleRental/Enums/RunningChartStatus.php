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

enum RunningChartStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Reversed = 'reversed';
}
