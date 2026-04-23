<?php

declare(strict_types=1);

namespace Modules\HR\Domain\ValueObjects;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case HALF_DAY = 'half_day';
    case ON_LEAVE = 'on_leave';
    case HOLIDAY = 'holiday';
    case WEEKEND = 'weekend';
    case WORK_FROM_HOME = 'work_from_home';
}
