<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalReplacementStatus: string
{
    case Draft = 'draft';
    case Completed = 'completed';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
}
