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

final readonly class CompOffClaimData
{
    public function __construct(
        public int $employeeId,
        public string $workedDate,
        public string $reason,
        public string $claimedDays = '1.00',
        public ?int $attendanceRecordId = null,
        public ?int $organizationUnitId = null,
    ) {}
}
