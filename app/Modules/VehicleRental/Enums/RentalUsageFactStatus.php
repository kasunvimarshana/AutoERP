<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalUsageFactStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Reversed = 'reversed';
}
