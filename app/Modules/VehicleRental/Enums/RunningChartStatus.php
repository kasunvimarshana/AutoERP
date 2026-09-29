<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RunningChartStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Reversed = 'reversed';
}
