<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\DTOs;

final readonly class VehicleServiceEmployeeAssignmentBatchEntryData
{
    public function __construct(
        public int $lineId,
        public VehicleServiceEmployeeAssignmentData $assignment,
    ) {}
}
