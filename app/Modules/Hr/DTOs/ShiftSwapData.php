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

final readonly class ShiftSwapData
{
    public function __construct(
        public int $requesterEmployeeId,
        public int $targetEmployeeId,
        public string $swapDate,
        public string $targetSwapDate,
        public string $reason,
        public ?int $requesterShiftId = null,
        public ?int $targetShiftId = null,
        public ?int $organizationUnitId = null,
    ) {}
}
