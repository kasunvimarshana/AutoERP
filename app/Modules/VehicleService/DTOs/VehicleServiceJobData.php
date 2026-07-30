<?php

declare(strict_types=1);

namespace Modules\VehicleService\DTOs;

use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceJobType;

final readonly class VehicleServiceJobData
{
    public function __construct(
        public int $tenantId,
        public ?string $jobDate,
        public int $customerId,
        public int $vehicleId,
        public VehicleServiceJobType $type = VehicleServiceJobType::FullService,
        public ?int $billToCustomerId = null,
        public ?int $organizationUnitId = null,
        public ?string $jobNumber = null,
        public ?string $manualJobCardNumber = null,
        public ?string $expectedDeliveryDate = null,
        public ?int $supervisorEmployeeId = null,
        public ?VehicleServiceCommissionType $supervisorCommissionType = null,
        public ?string $supervisorCommissionValue = null,
        public ?string $odometerReading = null,
        public ?string $nextServiceMileage = null,
        public ?string $fuelLevel = null,
        public ?string $priority = null,
        public ?string $notes = null,
        public ?string $customerComplaint = null,
        public bool $customerComplaintProvided = false,
        public ?int $createdBy = null,
    ) {}
}
