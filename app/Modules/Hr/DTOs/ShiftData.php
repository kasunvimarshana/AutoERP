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

use Modules\Hr\Enums\ShiftStatus;

final readonly class ShiftData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $startTime,
        public string $endTime,
        public string $color = '#3B82F6',
        public bool $isOvernight = false,
        public int $graceInMinutes = 15,
        public int $graceOutMinutes = 15,
        public int $halfDayThresholdMinutes = 240,
        public int $fullDayThresholdMinutes = 480,
        public bool $isBreakPaid = false,
        public bool $autoDeductBreak = false,
        public int $autoDeductBreakMinutes = 0,
        public int $autoDeductThresholdMinutes = 300,
        public bool $isFlexible = false,
        public ShiftStatus $status = ShiftStatus::Active,
        public ?string $notes = null,
        public ?int $organizationUnitId = null,
    ) {}
}
