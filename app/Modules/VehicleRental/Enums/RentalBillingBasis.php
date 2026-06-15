<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalBillingBasis: string
{
    case CalendarMonth = 'calendar_month';
    case AnniversaryMonth = 'anniversary_month';
    case FixedThirtyDay = 'fixed_30_day';
    case ExactDayCount = 'exact_day_count';
    case ContractualPeriod = 'contractual_period';
}
