<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\VehicleAttributeData;
use Modules\Vehicle\Enums\VehicleAttributeDataType;

abstract class VehicleAttributeRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'attribute_key' => ['required', 'string', 'max:150'],
            'attribute_value' => ['nullable', 'string'],
            'data_type' => ['nullable', Rule::enum(VehicleAttributeDataType::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toData(): VehicleAttributeData
    {
        return new VehicleAttributeData(
            attributeKey: (string) $this->input('attribute_key'),
            attributeValue: $this->filled('attribute_value') ? (string) $this->input('attribute_value') : null,
            dataType: VehicleAttributeDataType::from((string) $this->input('data_type', VehicleAttributeDataType::Text->value)),
            sortOrder: (int) $this->input('sort_order', 0),
        );
    }
}
