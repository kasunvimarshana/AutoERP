<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\Enums\VehicleAttributeDataType;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Http\Requests\Concerns\MapsVehicleData;
use Modules\Vehicle\Models\VehicleOwnership;

final class StoreVehicleWithRelationsRequest extends TenantScopedRequest
{
    use MapsVehicleData;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'vehicle' => ['required', 'array'],
            ...StoreVehicleRequest::vehicleRules('vehicle.'),
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['required', Rule::enum(VehicleDocumentType::class)],
            'documents.*.document_number' => ['nullable', 'string', 'max:150'],
            'documents.*.issued_date' => ['nullable', 'date'],
            'documents.*.expiry_date' => ['nullable', 'date'],
            'documents.*.file_path' => ['nullable', 'string', 'max:500'],
            'documents.*.status' => ['nullable', Rule::enum(VehicleDocumentStatus::class)],
            'documents.*.notes' => ['nullable', 'string'],
            'ownerships' => ['nullable', 'array'],
            'ownerships.*.owner_type' => ['required', 'string', Rule::in(VehicleOwnership::SUPPORTED_OWNER_TYPES)],
            'ownerships.*.owner_id' => ['nullable', 'integer', 'min:1'],
            'ownerships.*.ownership_type' => ['required', Rule::enum(VehicleOwnershipType::class)],
            'ownerships.*.started_at' => ['required', 'date'],
            'ownerships.*.ended_at' => ['nullable', 'date'],
            'ownerships.*.is_current' => ['nullable', 'boolean'],
            'ownerships.*.notes' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.attribute_key' => ['required', 'string', 'max:150'],
            'attributes.*.attribute_value' => ['nullable', 'string'],
            'attributes.*.data_type' => ['nullable', Rule::enum(VehicleAttributeDataType::class)],
            'attributes.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toData(): CreateVehicleData
    {
        $validated = $this->validated();
        return $this->mapVehicleData((array) $validated['vehicle'], $validated);
    }
}
