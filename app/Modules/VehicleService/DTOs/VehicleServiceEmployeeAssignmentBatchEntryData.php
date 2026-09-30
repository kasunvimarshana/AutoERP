<?php

declare(strict_types=1);

namespace Modules\VehicleService\DTOs;

final readonly class VehicleServiceEmployeeAssignmentBatchEntryData
{
    public function __construct(
        public int $lineId,
        public VehicleServiceEmployeeAssignmentData $assignment,
    ) {}
}
