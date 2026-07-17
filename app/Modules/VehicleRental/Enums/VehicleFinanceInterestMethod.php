<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum VehicleFinanceInterestMethod: string
{
    case Flat = 'flat';
    case ReducingBalance = 'reducing_balance';
    case Custom = 'custom';
}
