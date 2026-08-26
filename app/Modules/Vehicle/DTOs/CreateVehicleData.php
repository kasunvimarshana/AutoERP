<?php

declare(strict_types=1);

namespace Modules\Vehicle\DTOs;

use Modules\Vehicle\Enums\VehicleFuelType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Enums\VehicleTransmissionType;
use Modules\Vehicle\Data\VehicleOwnershipDraftData;

final readonly class CreateVehicleData
{
    /**
     * @param array<string, mixed>|null $metadata
     * @param list<VehicleDocumentData> $documents
     * @param list<VehicleAttributeData> $attributes
     * @param list<VehicleOwnershipDraftData> $ownerships
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId = null,
        public ?string $vehicleNumber = null,
        public ?string $code = null,
        public ?int $vehicleMakeId = null,
        public ?int $vehicleModelId = null,
        public ?int $vehicleTypeId = null,
        public ?int $vehicleCategoryId = null,
        public ?string $registrationNumber = null,
        public ?string $chassisNumber = null,
        public ?string $engineNumber = null,
        public ?string $vinNumber = null,
        public ?int $manufactureYear = null,
        public ?string $registrationDate = null,
        public ?string $color = null,
        public ?VehicleFuelType $fuelType = null,
        public ?VehicleTransmissionType $transmissionType = null,
        public ?string $odometerReading = null,
        public ?string $odometerUnit = null,
        public ?string $fuelLevel = null,
        public VehicleStatus $status = VehicleStatus::Active,
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?int $createdBy = null,
        public array $documents = [],
        public array $attributes = [],
        public array $ownerships = [],
    ) {}
}
