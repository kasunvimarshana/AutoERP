<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalRunningChartStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Reversed = 'reversed';
}
