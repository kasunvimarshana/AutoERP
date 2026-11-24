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

use Modules\Hr\Enums\LeaveDurationType;

final readonly class LeaveRequestData
{
    public function __construct(
        public int $employeeId,
        public int $leaveTypeId,
        public string $startDate,
        public string $endDate,
        public string $totalDays,
        public string $reason,
        public LeaveDurationType $durationType = LeaveDurationType::FullDay,
        public ?int $attachmentDocumentId = null,
        public ?int $organizationUnitId = null,
    ) {}
}
