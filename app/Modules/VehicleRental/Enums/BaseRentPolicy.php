<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum BaseRentPolicy: string
{
    case ActualCalendarDays = 'actual_calendar_days_v1';
}
