<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCalculationSourceStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Reversed = 'reversed';
}
