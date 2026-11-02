<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCalculationLineStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Reversed = 'reversed';
}
