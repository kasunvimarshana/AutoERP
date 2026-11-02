<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\Enums\VehicleAttributeDataType;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;
use Modules\Vehicle\Http\Requests\Concerns\MapsVehicleData;

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
            'documents.*.file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
            'documents.*.file_path' => ['prohibited'],
            'documents.*.status' => ['nullable', Rule::enum(VehicleDocumentStatus::class)],
            'documents.*.notes' => ['nullable', 'string'],
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
