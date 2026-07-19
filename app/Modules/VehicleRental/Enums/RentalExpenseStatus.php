<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExpenseStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Allocated = 'allocated';
    case Reversed = 'reversed';
}
