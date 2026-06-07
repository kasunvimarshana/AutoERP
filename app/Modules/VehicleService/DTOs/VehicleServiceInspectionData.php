<?php

declare(strict_types=1);

namespace Modules\VehicleService\DTOs;

final readonly class VehicleServiceInspectionData
{
    public function __construct(
        public ?string $customerComplaint = null,
        public ?string $inspectionNotes = null,
        public ?string $diagnosis = null,
        public ?string $recommendedWork = null,
        public ?string $odometerReading = null,
        public ?string $fuelLevel = null,
        public ?int $inspectedBy = null,
        public bool $markInspected = false,
    ) {}
}
