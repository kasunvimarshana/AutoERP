<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalCalculationStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Reversed = 'reversed';
}
