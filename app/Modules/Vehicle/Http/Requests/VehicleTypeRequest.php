<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\VehicleTypeData;

abstract class VehicleTypeRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toData(): VehicleTypeData
    {
        return new VehicleTypeData(
            tenantId: $this->tenantId(),
            code: (string) $this->input('code'),
            name: (string) $this->input('name'),
            organizationUnitId: $this->organizationUnitId(),
            description: $this->filled('description') ? (string) $this->input('description') : null,
            isActive: (bool) $this->input('is_active', true),
            sortOrder: (int) $this->input('sort_order', 0),
        );
    }
}
