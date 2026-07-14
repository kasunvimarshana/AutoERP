<?php

declare(strict_types=1);

namespace Modules\VehicleService\DTOs;

use Modules\VehicleService\Enums\VehicleServiceCommissionType;

final readonly class VehicleServiceEmployeeAssignmentData
{
    public function __construct(
        public int $employeeId,
        public string $roleType,
        public string $assignedHours = '0.000000',
        public string $rate = '0.000000',
        public ?VehicleServiceCommissionType $commissionType = null,
        public ?string $commissionValue = null,
        public string $status = 'assigned',
    ) {}
}
