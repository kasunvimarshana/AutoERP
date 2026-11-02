<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalBillingPeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Finalized = 'finalized';
    case Reopened = 'reopened';
    case Cancelled = 'cancelled';
}
