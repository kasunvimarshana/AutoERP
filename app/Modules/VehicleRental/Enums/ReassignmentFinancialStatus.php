<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum ReassignmentFinancialStatus: string
{
    case Pending = 'pending';
    case Settled = 'settled';
    case None = 'none';
}
