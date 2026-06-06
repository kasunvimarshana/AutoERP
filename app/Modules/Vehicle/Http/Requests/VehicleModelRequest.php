<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\VehicleModelData;

abstract class VehicleModelRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_make_id' => ['required', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:150'],
            'year_from' => ['nullable', 'integer', 'between:1886,'.(((int) date('Y')) + 1)],
            'year_to' => ['nullable', 'integer', 'between:1886,'.(((int) date('Y')) + 1), 'gte:year_from'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): VehicleModelData
    {
        return new VehicleModelData(
            tenantId: $this->tenantId(),
            vehicleMakeId: (int) $this->input('vehicle_make_id'),
            code: (string) $this->input('code'),
            name: (string) $this->input('name'),
            organizationUnitId: $this->organizationUnitId(),
            yearFrom: $this->filled('year_from') ? (int) $this->input('year_from') : null,
            yearTo: $this->filled('year_to') ? (int) $this->input('year_to') : null,
            description: $this->filled('description') ? (string) $this->input('description') : null,
            isActive: (bool) $this->input('is_active', true),
        );
    }
}
