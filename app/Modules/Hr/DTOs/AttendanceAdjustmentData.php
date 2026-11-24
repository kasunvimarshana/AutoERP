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

use Modules\Hr\Enums\AttendanceStatus;

final readonly class AttendanceAdjustmentData
{
    public function __construct(
        public int $attendanceRecordId,
        public AttendanceStatus $newStatus,
        public int $newWorkedMinutes,
        public int $newOvertimeMinutes,
        public string $adjustmentReason,
        public ?int $adjustedBy = null,
        public ?int $regularizationId = null,
    ) {}
}
