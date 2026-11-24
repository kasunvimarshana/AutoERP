<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\DTOs;

use Modules\Hr\Models\HrShift;

final readonly class ResolvedShiftData
{
    public function __construct(
        public ?HrShift $shift,
        public string $shiftDate,
        public bool $isWorkingDay,
        public bool $isRestDay,
        public bool $isHoliday,
        public ?string $holidayName,
        public ?string $startTime,
        public ?string $endTime,
        public bool $isOvernight,
        public int $scheduledMinutes,
        public int $graceInMinutes,
        public int $graceOutMinutes,
        public string $resolutionSource, // 'reallocation', 'swap', 'specific_date', 'date_range', 'recurring', 'holiday', 'rest_day'
    ) {}
}
