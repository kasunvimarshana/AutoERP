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

use Modules\Hr\Enums\ShiftReallocationReason;

final readonly class ShiftReallocationData
{
    public function __construct(
        public int $absentEmployeeId,
        public int $coverEmployeeId,
        public int $shiftId,
        public string $reallocationDate,
        public ShiftReallocationReason $reasonCode = ShiftReallocationReason::SickCover,
        public ?string $notes = null,
        public ?int $reallocatedBy = null,
        public ?int $organizationUnitId = null,
    ) {}
}
