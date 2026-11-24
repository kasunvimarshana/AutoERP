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

use Modules\Hr\Enums\ShiftAssignmentType;

final readonly class ShiftAssignmentData
{
    public function __construct(
        public int $employeeId,
        public int $shiftId,
        public ShiftAssignmentType $assignmentType,
        public ?int $dayOfWeek = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public bool $isCustomTiming = false,
        public ?string $customStartTime = null,
        public ?string $customEndTime = null,
        public ?int $assignedBy = null,
        public ?int $organizationUnitId = null,
    ) {}
}
