<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum MileagePolicy: string
{
    case CommercialCalendarCycles = 'commercial_calendar_cycles_v1';

    public const EMPTY_POOL_REVISION = 0;

    public const COMPONENT = 'excess_distance';

    public const MONTHS_PER_YEAR = 12;
}
