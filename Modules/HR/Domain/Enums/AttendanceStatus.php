<?php

namespace Modules\HR\Domain\Enums;

enum AttendanceStatus: string
{
    case Present   = 'present';
    case Absent    = 'absent';
    case OnLeave   = 'on_leave';
    case HalfDay   = 'half_day';
}
