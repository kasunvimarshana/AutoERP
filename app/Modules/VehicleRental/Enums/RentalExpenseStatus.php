<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExpenseStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Charged = 'charged';
}
