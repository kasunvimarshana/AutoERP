<?php

declare(strict_types=1);

namespace Modules\Vehicle\DTOs;

use Modules\Vehicle\Enums\VehicleFuelType;
use Modules\Vehicle\Enums\VehicleTransmissionType;

final readonly class UpdateVehicleData
{
    /**
     * @param array<string, mixed>|null $metadata
     * @param list<VehicleDocumentData>|null $documents
     * @param list<VehicleOwnershipData>|null $ownerships
     * @param list<VehicleAttributeData>|null $attributes
     * @param list<string> $provided
     */
    public function __construct(
        public ?int $organizationUnitId = null,
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
        public ?string $notes = null,
        public ?array $metadata = null,
        public ?array $documents = null,
        public ?array $ownerships = null,
        public ?array $attributes = null,
        public array $provided = [],
    ) {}
}
