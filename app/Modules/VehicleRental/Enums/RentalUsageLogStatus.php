<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalUsageLogStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
