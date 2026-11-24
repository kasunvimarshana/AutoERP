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

use Modules\Hr\Enums\LeaveAccrualFrequency;

final readonly class LeavePolicyData
{
    public function __construct(
        public int $leaveTypeId,
        public string $name,
        public string $annualAllocationDays,
        public string $effectiveFrom,
        public LeaveAccrualFrequency $accrualFrequency = LeaveAccrualFrequency::AnnualUpfront,
        public string $maxCarryForwardDays = '5.00',
        public int $carryForwardExpiryMonths = 3,
        public int $maxConsecutiveDays = 10,
        public bool $probationRestricted = false,
        public ?string $effectiveTo = null,
        public ?int $departmentId = null,
        public ?int $employmentTypeId = null,
        public ?int $organizationUnitId = null,
    ) {}
}
