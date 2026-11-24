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

use Modules\Hr\Enums\RegularizationType;

final readonly class AttendanceRegularizationData
{
    public function __construct(
        public int $employeeId,
        public string $attendanceDate,
        public RegularizationType $regularizationType,
        public string $employeeNotes,
        public ?string $proposedClockIn = null,
        public ?string $proposedClockOut = null,
        public string $reasonCode = 'forgot_to_punch',
        public ?int $attendanceRecordId = null,
        public ?int $organizationUnitId = null,
    ) {}
}
