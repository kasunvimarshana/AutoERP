<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests\Concerns;

use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\DTOs\VehicleAttributeData;
use Modules\Vehicle\DTOs\VehicleDocumentData;
use Modules\Vehicle\DTOs\VehicleOwnershipData;
use Modules\Vehicle\Enums\VehicleAttributeDataType;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;
use Modules\Vehicle\Enums\VehicleFuelType;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Enums\VehicleTransmissionType;

trait MapsVehicleData
{
    private function mapVehicleData(array $vehicle, array $relations = []): CreateVehicleData
    {
        return new CreateVehicleData(
            tenantId: $this->tenantId(),
            organizationUnitId: $this->organizationUnitId(),
            vehicleNumber: $this->nullableString($vehicle, 'vehicle_number'),
            code: $this->nullableString($vehicle, 'code'),
            vehicleMakeId: $this->nullableInt($vehicle, 'vehicle_make_id'),
            vehicleModelId: $this->nullableInt($vehicle, 'vehicle_model_id'),
            vehicleTypeId: $this->nullableInt($vehicle, 'vehicle_type_id'),
            vehicleCategoryId: $this->nullableInt($vehicle, 'vehicle_category_id'),
            registrationNumber: $this->nullableString($vehicle, 'registration_number'),
            chassisNumber: $this->nullableString($vehicle, 'chassis_number'),
            engineNumber: $this->nullableString($vehicle, 'engine_number'),
            vinNumber: $this->nullableString($vehicle, 'vin_number'),
            manufactureYear: $this->nullableInt($vehicle, 'manufacture_year'),
            registrationDate: $this->nullableString($vehicle, 'registration_date'),
            color: $this->nullableString($vehicle, 'color'),
            fuelType: isset($vehicle['fuel_type']) && $vehicle['fuel_type'] !== null ? VehicleFuelType::from((string) $vehicle['fuel_type']) : null,
            transmissionType: isset($vehicle['transmission_type']) && $vehicle['transmission_type'] !== null ? VehicleTransmissionType::from((string) $vehicle['transmission_type']) : null,
            odometerReading: (string) ($vehicle['odometer_reading'] ?? '0.000000'),
            odometerUnit: $this->nullableString($vehicle, 'odometer_unit'),
            fuelLevel: $this->nullableString($vehicle, 'fuel_level'),
            status: VehicleStatus::from((string) ($vehicle['status'] ?? VehicleStatus::Active->value)),
            notes: $this->nullableString($vehicle, 'notes'),
            metadata: $vehicle['metadata'] ?? null,
            createdBy: $this->currentUserId(),
            documents: array_map(fn (array $row): VehicleDocumentData => $this->mapDocument($row), $relations['documents'] ?? []),
            ownerships: array_map(fn (array $row): VehicleOwnershipData => $this->mapOwnership($row), $relations['ownerships'] ?? []),
            attributes: array_map(fn (array $row): VehicleAttributeData => $this->mapAttribute($row), $relations['attributes'] ?? []),
        );
    }

    private function mapDocument(array $row): VehicleDocumentData
    {
        return new VehicleDocumentData(
            documentType: VehicleDocumentType::from((string) $row['document_type']),
            documentNumber: $row['document_number'] ?? null,
            issuedDate: $row['issued_date'] ?? null,
            expiryDate: $row['expiry_date'] ?? null,
            filePath: $row['file_path'] ?? null,
            status: VehicleDocumentStatus::from((string) ($row['status'] ?? VehicleDocumentStatus::Pending->value)),
            notes: $row['notes'] ?? null,
        );
    }

    private function mapOwnership(array $row): VehicleOwnershipData
    {
        return new VehicleOwnershipData(
            ownershipType: VehicleOwnershipType::from((string) $row['ownership_type']),
            startedAt: (string) $row['started_at'],
            ownerType: (string) $row['owner_type'],
            ownerId: isset($row['owner_id']) ? (int) $row['owner_id'] : null,
            endedAt: $row['ended_at'] ?? null,
            isCurrent: (bool) ($row['is_current'] ?? true),
            notes: $row['notes'] ?? null,
        );
    }

    private function mapAttribute(array $row): VehicleAttributeData
    {
        return new VehicleAttributeData(
            attributeKey: (string) $row['attribute_key'],
            attributeValue: isset($row['attribute_value']) ? (string) $row['attribute_value'] : null,
            dataType: VehicleAttributeDataType::from((string) ($row['data_type'] ?? VehicleAttributeDataType::Text->value)),
            sortOrder: (int) ($row['sort_order'] ?? 0),
        );
    }

    private function nullableInt(array $data, string $key): ?int
    {
        return isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
    }

    private function nullableString(array $data, string $key): ?string
    {
        return isset($data[$key]) && trim((string) $data[$key]) !== '' ? (string) $data[$key] : null;
    }
}
